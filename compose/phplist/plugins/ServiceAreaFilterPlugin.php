<?php
class ServiceAreaFilterPlugin extends phplistPlugin {
    public $name = "RSS Service Area Filter";
    public $version = "1.11.0";
    public $enabled = 1;

    /** Filtered HTML cached per message+recipient so the text part can be derived from it */
    private $filteredCache = [];

    /** Recipients with no matching items; their outgoing mail is blackholed in messageHeaders() */
    private $suppressed = [];

    /**
     * Skip subscribers who have no matching items at all, before any email is built.
     * Note: the [RSS] placeholder is usually not expanded yet at this stage, so this
     * only triggers when the campaign body already contains rss-service-item markup.
     */
    public function canSend($messageData, $userData) {
        $userId = !empty($userData['id']) ? (int)$userData['id'] : 0;
        $content = '';
        foreach (['message', 'textmessage'] as $k) {
            if (!empty($messageData[$k]) && is_string($messageData[$k])) {
                $content .= "\n" . $messageData[$k];
            }
        }
        if ($userId > 0 && stripos($content, 'rss-service-item') !== false) {
            $areas = $this->getUserAreas($userId, $content);
            list($f, $total, $kept) = $this->filterItems($content, $areas);
            if ($total > 0 && $kept === 0) {
                error_log("RSS Service Area Filter: [canSend=false] 0 items match user $userId. Not sending.");
                return false;
            }
        }
        return true;
    }

    private function cacheKey($messageid, $destination) {
        return (int)$messageid . '|' . strtolower(trim((string)$destination));
    }

    /**
     * Filter <article class="rss-service-item" data-area="..."> blocks for one subscriber.
     * Returns [filteredContent, totalItems, keptItems].
     */
    private function filterItems($content, array $userAreas) {
        $pattern = '/<article[^>]*class=["\'][^"\']*rss-service-item[^"\']*["\'][^>]*data-area=["\']([^"\']*)["\'][^>]*>(.*?)<\/article>/si';
        $total = 0;
        $kept = 0;

        $filtered = preg_replace_callback($pattern, function ($m) use ($userAreas, &$total, &$kept) {
            $total++;
            $itemArea = trim(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            foreach ($userAreas as $area) {
                if ($area === '') {
                    continue;
                }
                if (strcasecmp($itemArea, $area) === 0
                    || stripos($itemArea, $area) !== false
                    || stripos($area, $itemArea) !== false) {
                    $kept++;
                    return $m[0];
                }
            }
            return '';
        }, $content);

        return [$filtered, $total, $kept];
    }

    /**
     * Remove the "powered by phpList" credit (HTML block and text line).
     */
    private function stripPoweredBy($content) {
        if (!is_string($content) || $content === '') {
            return $content;
        }
        $content = preg_replace('/<p\b[^>]*class=["\'][^"\']*poweredby[^"\']*["\'][^>]*>.*?<\/p>/si', '', $content);
        $content = preg_replace('/^[ \t]*-+[ \t]*powered by phpList.*$/mi', '', $content);
        return $content;
    }

    private function htmlToText($html) {
        if (function_exists('HTML2Text')) {
            return HTML2Text($html);
        }
        if (class_exists('Html2Text\Html2Text')) {
            return \Html2Text\Html2Text::convert($html);
        }
        $html = preg_replace('/<(script|style)\b.*?<\/\1>/si', '', $html);
        $html = preg_replace('/<br\s*\/?>/i', "\n", $html);
        $html = preg_replace('/<\/(p|div|h[1-6]|li|tr|article)>/i', "\n\n", $html);
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return trim(preg_replace("/\n{3,}/", "\n\n", $text));
    }

    /**
     * HTML part: filter items, cache the result, and flag the recipient for
     * blackholing if nothing matches.
     */
    public function parseOutgoingHTMLMessage($messageid, $content, $destination, $userData = null) {
        $userId = !empty($userData['id']) ? (int)$userData['id'] : 0;
        $userAreas = $this->getUserAreas($userId, $content);

        list($filtered, $total, $kept) = $this->filterItems($content, $userAreas);

        if ($total > 0 && $kept === 0) {
            error_log("RSS Service Area Filter: [SUPPRESSED] 0 items matched for $destination. Mail will be blackholed.");
            $this->suppressed[strtolower(trim((string)$destination))] = true;
            // Return an empty body rather than false; the message is discarded in messageHeaders()
            return '';
        }

        if ($total > 0) {
            error_log("RSS Service Area Filter: HTML summary for $destination - processed $total, kept $kept.");
            // Remember this subscriber's filtered HTML for the text part
            $this->filteredCache[$this->cacheKey($messageid, $destination)] = $filtered;
        }

        return $this->stripPoweredBy($filtered);
    }

    /**
     * Filter a plain-text body by service-area sections.
     * Each section starts with a heading line containing a feed link, e.g.
     *   **Stikine <http://host/email/feeds/dist28.txt?123>
     *   [South Vancouver Island](http://host/email/feeds/dist01.txt?123)
     * and runs until the next such heading or the subscriber footer.
     * Returns [filteredText, totalSections, keptSections].
     */
    private function filterText($text, array $userAreas) {
        $headRe = '/^[ \t*#_>]*\[?\s*([^\]\[<>()\r\n]+?)\s*\]?\s*[<(]\s*https?:\/\/[^\s>)]*feeds\/dist\d+\.txt[^\s>)]*\s*[>)]?[ \t*_]*$/mi';
        $footRe = '/^\s*(Subscriber Services|This email was sent to|To make changes to your preferences|To unsubscribe)/mi';

        if (!preg_match_all($headRe, $text, $heads, PREG_OFFSET_CAPTURE | PREG_SET_ORDER) || empty($heads)) {
            return [$text, 0, 0];
        }

        $firstStart = $heads[0][0][1];
        $endAll = strlen($text);
        if (preg_match($footRe, $text, $fm, PREG_OFFSET_CAPTURE, $firstStart)) {
            $endAll = $fm[0][1];
        }

        $out = substr($text, 0, $firstStart);
        $total = 0;
        $kept = 0;
        $n = count($heads);
        for ($i = 0; $i < $n; $i++) {
            $secStart = $heads[$i][0][1];
            $secEnd = ($i + 1 < $n) ? $heads[$i + 1][0][1] : $endAll;
            $section = substr($text, $secStart, $secEnd - $secStart);
            $itemArea = trim(html_entity_decode($heads[$i][1][0], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $total++;
            foreach ($userAreas as $area) {
                if ($area === '') {
                    continue;
                }
                if (strcasecmp($itemArea, $area) === 0
                    || stripos($itemArea, $area) !== false
                    || stripos($area, $itemArea) !== false) {
                    $kept++;
                    $out .= $section;
                    break;
                }
            }
        }
        $out .= substr($text, $endAll);

        return [$out, $total, $kept];
    }

    /**
     * Text part: filter the sections directly in the plain text.
     */
    public function parseOutgoingTextMessage($messageid, $content, $destination, $userData = null) {
        $userId = !empty($userData['id']) ? (int)$userData['id'] : 0;
        $userAreas = $this->getUserAreas($userId, $content);

        list($filtered, $total, $kept) = $this->filterText($content, $userAreas);
        error_log("RSS Service Area Filter: TEXT hook for $destination - sections $total, kept $kept, areas: " . implode('; ', $userAreas));

        if ($total > 0) {
            return $this->stripPoweredBy($filtered);
        }

        $key = $this->cacheKey($messageid, $destination);
        if (isset($this->filteredCache[$key])) {
            return $this->stripPoweredBy($this->htmlToText($this->filteredCache[$key]));
        }

        return $this->stripPoweredBy($content);
    }

    /**
     * Last-stage hook, called once PHPMailer is fully built.
     *  - Blackholes mail for subscribers with no matching items. The recipient is kept
     *    (PHPMailer throws "You must provide at least one recipient" otherwise) but the
     *    transport is swapped for a no-op, so phpList records a success and the campaign
     *    can finish instead of retrying forever.
     *  - Strips the powered-by credit and filters AltBody if the text hook didn't.
     */
    public function messageHeaders($phpmailer) {
        $toList = $phpmailer->getToAddresses();
        $toEmail = !empty($toList[0][0]) ? strtolower(trim($toList[0][0])) : '';

        if ($toEmail !== '' && !empty($this->suppressed[$toEmail])) {
            error_log("RSS Service Area Filter: [messageHeaders] blackholing mail to $toEmail (no matching items).");
            unset($this->suppressed[$toEmail]);
            // Keep the recipient, but hand the message to a no-op transport.
            // Confirm the path with `which true` on your server (often /bin/true or /usr/bin/true).
            $phpmailer->Mailer   = 'sendmail';
            $phpmailer->Sendmail = '/bin/true';
            return [];
        }

        // Always strip the credit, even if the hooks above ran before it was added
        $phpmailer->Body = $this->stripPoweredBy($phpmailer->Body);
        if (!empty($phpmailer->AltBody)) {
            $phpmailer->AltBody = $this->stripPoweredBy($phpmailer->AltBody);

            $email = !empty($toList[0][0]) ? $toList[0][0] : '';
            if ($email !== '') {
                $esc = function_exists('sql_escape') ? sql_escape($email) : addslashes($email);
                $res = Sql_Query(sprintf("SELECT id FROM %s WHERE email = '%s'", $GLOBALS['tables']['user'], $esc));
                $row = Sql_Fetch_Row($res);
                $userId = $row ? (int)$row[0] : 0;
                if ($userId > 0) {
                    list($filtered, $total, $kept) = $this->filterText($phpmailer->AltBody, $this->getUserAreas($userId, $phpmailer->AltBody));
                    if ($total > 0) {
                        error_log("RSS Service Area Filter: AltBody filtered in messageHeaders for $email - sections $total, kept $kept.");
                        $phpmailer->AltBody = $filtered;
                    }
                }
            }
        }
        return [];
    }

    /**
     * Resolve the user's checked areas into actual text names
     */
    private function getUserAreas($userId, $content = '') {
        $areas = [];

        if ($userId > 0) {
            $res = Sql_Query(sprintf(
                "SELECT id, tablename, type FROM %s WHERE LOWER(name) = 'dbc:servicearea'",
                $GLOBALS['tables']['attribute']
            ));

            if ($attr = Sql_Fetch_Assoc($res)) {
                $attrId = (int)$attr['id'];
                $tableName = $attr['tablename'];

                if (!empty($tableName)) {
                    $listTable = (isset($GLOBALS['table_prefix']) ? $GLOBALS['table_prefix'] : '') . 'listattr_' . $tableName;
                    $valRes = Sql_Query(sprintf(
                        "SELECT val.name FROM %s ua
                         JOIN %s val ON FIND_IN_SET(val.id, ua.value)
                         WHERE ua.attributeid = %d AND ua.userid = %d",
                        $GLOBALS['tables']['user_attribute'],
                        $listTable,
                        $attrId,
                        $userId
                    ));
                    while ($row = Sql_Fetch_Row($valRes)) {
                        $areas[] = trim($row[0]);
                    }
                }

                if (empty($areas)) {
                    $valRes = Sql_Query(sprintf(
                        "SELECT value FROM %s WHERE attributeid = %d AND userid = %d",
                        $GLOBALS['tables']['user_attribute'],
                        $attrId,
                        $userId
                    ));
                    if ($row = Sql_Fetch_Row($valRes)) {
                        $raw = explode(';', $row[0]);
                        $areas = array_filter(array_map('trim', $raw));
                    }
                }
            }
        }

        if (empty($areas) && !empty($content)) {
            if (preg_match('/(?:Your Service Area|DBC:SERVICEAREA)[:\s]+([^<\r\n]+)/i', $content, $match)) {
                $raw = explode(';', $match[1]);
                $areas = array_filter(array_map('trim', $raw));
            }
        }

        return array_values(array_unique($areas));
    }
}