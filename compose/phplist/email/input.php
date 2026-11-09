<?php
	// Input fields used in subscription/update and unsubscribe process.

require_once ('submit.php');
require_once('utility.php');

require('config.php');
global $SPAGE_ID;

global $SCREEN_MODE;		// defined on each page.
global $HIDE_INPUT;		// set in submit.php

/* Same input for both the subscribe and preferences screens. */
if (($SCREEN_MODE == "subscribe" || $SCREEN_MODE == "preferences")
	&& (!isset($HIDE_INPUT) || $HIDE_INPUT == false)){
?>
	<h1 id="subscribe_header1" style="display:none;">Load Restrictions Notifications</h1>

	<div id="step0">
		<div id="subscribe_disclaimer">
			<h1>Load Restrictions Subscription Service Disclaimer</h1>
			<ul>
				<li>
					The Load Restriction notification service is provided as a public service by the Government of British Columbia, Box 9411, Victoria, British Columbia, Canada V8W 9V1.
				</li>
				<li>
					Under no circumstances will the Government of British Columbia be liable to any person or business entity for any direct, indirect, special, incidental, consequential, or other damages based on any use of this Web site or any other Web site to which this site is linked, including, without limitation, any lost profits, business interruption, or loss of programs or information, even if the Government of British Columbia has been specifically advised of the possibility of such damages.
				</li>
				<li>
					This notification service and all of the information it contains are provided "as-is" without warranty of any kind, whether express or implied. All implied warranties including, without limitation, implied warranties of merchantability, fitness for a particular purpose, and non- infringement, are hereby disclaimed.
				</li>
			</ul>
			<h1>Consent</h1>
			<ul>
				<li>
					Your email address will be stored in a secure database and used for the sole purpose of sending you Load Restriction notifications. This collection is authorized under the Freedom of Information and Protection of Privacy Act Section 26(c).
				</li>
				<li>
					If you have any concerns or questions about the collection or use of your email address for this service, please contact:
				</li>
				<li>
					DriveBC Business Manager<br/>
					Transportation Services<br/>
					PO Box 9850 Stn Prov Govt, Victoria BC V8W 9T5<br/>
					<a href="mailto:MOTIDriveBC@gov.bc.ca">MOTIDriveBC@gov.bc.ca</a><br/>
				</li>
				<li>
					By proceeding, you are accepting this disclaimer and consenting to the use of your email address for prescribed purpose.
				</li>
			</ul>
		    <div align="center"><input type="button" id="dsclmr_accept_btn" align="middle" value="Accept and Continue" /></div>
		    <br/>
		</div>
	</div>


	<!-- PAGE 1 -->
    <fieldset id='step1' style="display:none;">
        <legend>Step 1 of 3</legend>
		<div id="address">
			<label class="emailLbl" for="email">Enter email address:&nbsp;</label>
			<input class="required emailTxt" id="email" name="email" type="email"/>
			<p/>
			<label class="emailLbl" for="emailconfirm" id="emailconfirmlbl">Re-enter email address:&nbsp;</label>
			<input class="required emailTxt" id="emailconfirm" name="emailconfirm" type="email"/>
		</div>
		<div id="format">
			<label class="formatLbl jq-clearfix">
				Which email format do you prefer?
				<span id="faq_wrapper_prefs">
					<img alt"faq" src="images/icons/faq.png"/>
					<div class="jq-popup">
						<p><b>HTML</b> emails use formatted text and may include active web address links.  Select <b>Plain Text</b> to receive notifications without display formatting.</p>
					</div>
				</span>
			</label>
			<input id="htmlFormat1" type="radio" name="htmlemail" value="1" checked />HTML
			<input id="htmlFormat2" type="radio" name="htmlemail" value="0" />Plain Text
		</div>
		<a id="subscribeForm-next-1" href="javascript:void(0);" class="button-next">Next &gt;</a>
	</fieldset>

	<!-- PAGE 2 -->
    <fieldset id='step2' style="display:none;">
		<legend>Step 2 of 3</legend>
		<img src="images/SLR_Map_province_all.jpg" />

		<h2>Service Areas by Region</h2>
		<ul style="width:19em">
			<li>South Coast Region</li>
			<li><input type="checkbox" name="ServiceArea[]" id="ServiceArea0" value="1" />1 - South Vancouver Island</li>
			<li><input type="checkbox" name="ServiceArea[]" id="ServiceArea1" value="2" />2 - Central Vancouver Island</li>
			<li><input type="checkbox" name="ServiceArea[]" id="ServiceArea2" value="3" />3 - North Vancouver Island</li>
			<li><input type="checkbox" name="ServiceArea[]" id="ServiceArea3" value="4" />4 - Howe Sound</li>
			<li><input type="checkbox" name="ServiceArea[]" id="ServiceArea4" value="5" />5 - Sunshine Coast</li>
			<li><input type="checkbox" name="ServiceArea[]" id="ServiceArea5" value="6" />6 - Lower Mainland</li>
			<li><input type="checkbox" name="ServiceArea[]" id="ServiceArea6" value="7" />7 - Fraser Valley</li>
		</ul>
		<ul style="width:18em">
			<li>Southern Interior Region</li>
			<li><input type="checkbox" name="ServiceArea[]" id="ServiceArea7" value="8"  />8 - South Okanagan</li>
			<li><input type="checkbox" name="ServiceArea[]" id="ServiceArea8" value="9"  />9 - Kootenay Boundary</li>
			<li><input type="checkbox" name="ServiceArea[]" id="ServiceArea9" value="10"  />10 - Central Kootenay</li>
			<li><input type="checkbox" name="ServiceArea[]" id="ServiceArea10" value="11" />11 - East Kootenay</li>
			<li><input type="checkbox" name="ServiceArea[]" id="ServiceArea11" value="12" />12 - Selkirk</li>
			<li><input type="checkbox" name="ServiceArea[]" id="ServiceArea12" value="13" />13 - Okanagan-Shuswap</li>
			<li><input type="checkbox" name="ServiceArea[]" id="ServiceArea13" value="14" />14 - Nicola</li>
			<li><input type="checkbox" name="ServiceArea[]" id="ServiceArea14" value="15" />15 - Thompson</li>
			<li><input type="checkbox" name="ServiceArea[]" id="ServiceArea15" value="16" />16 - South Cariboo</li>
			<li><input type="checkbox" name="ServiceArea[]" id="ServiceArea16" value="17" />17 - Central Cariboo</li>
			<li><input type="checkbox" name="ServiceArea[]" id="ServiceArea17" value="18" />18 - North Cariboo</li>
		</ul>
		<ul style="width:13em;padding:0 0 2em 0;">
			<li>Northern Region</li>
			<li><input type="checkbox" name="ServiceArea[]" id="ServiceArea18" value="19" />19 - Fort George</li>
			<li><input type="checkbox" name="ServiceArea[]" id="ServiceArea19" value="20" />20 - Robson</li>
			<li><input type="checkbox" name="ServiceArea[]" id="ServiceArea20" value="21" />21 - South Peace</li>
			<li><input type="checkbox" name="ServiceArea[]" id="ServiceArea21" value="22" />22 - North Peace</li>
			<li><input type="checkbox" name="ServiceArea[]" id="ServiceArea22" value="23" />23 - Nechako</li>
			<li><input type="checkbox" name="ServiceArea[]" id="ServiceArea23" value="24" />24 - Lakes</li>
			<li><input type="checkbox" name="ServiceArea[]" id="ServiceArea24" value="25" />25 - Bulkley Nass</li>
			<li><input type="checkbox" name="ServiceArea[]" id="ServiceArea25" value="26" />26 - Skeena</li>
			<li><input type="checkbox" name="ServiceArea[]" id="ServiceArea26" value="27" />27 - North Coast</li>
			<li><input type="checkbox" name="ServiceArea[]" id="ServiceArea27" value="28" />28 - Stikine</li>
		</ul>

		<a id="subscribeForm-back-2" href="javascript:void(0);" class="button-back" style="margin-top:1em">&lt; Back</a>
		<a id="subscribeForm-next-2" href="javascript:void(0);" class="button-next" style="margin-top:1em">Next &gt;</a>
	</fieldset>

	<!-- Page 3 -->
    <fieldset id='step3' style="display:none;">
		<legend>Step 3 of 3</legend>
		<div id="filter_review">
			<ul class="newline">
				<li style="display:inline">Email Address:</li>
				<li><span id="cfrm_email"></span></li>
			</ul>
			<ul class="newline">
				<li>Email Format:</li>
				<li><span id="cfrm_format"></span></li>
			</ul>
			<ul class="newline">
				<li><span id="result_area_route"></span></li>
				<li><span id="cfrm_area_route"></span></li>
			</ul>
		</div>


		<a id="subscribeForm-back-3" href="javascript:void(0);" class="button-back"style="margin-top:1em">&lt; Back</a>
	    <input type="submit" name="submit" class="finish" style="margin-top:1em" value="Submit" />
	</fieldset>
	<div class="validation-error"></div>
<?php
}

/* Input for the unsubscribe screen */
else if (($SCREEN_MODE == "unsubscribe")
	&& (!isset($HIDE_INPUT) || $HIDE_INPUT == false))
{
?>
	<h1>Unsubscribe to Load Restriction Notifications by Email</h1>
	<ul>
		<li>To cancel your load restriction email subscription service, click "Unsubscribe".</li>
		<br/>
		<li><textarea style="display:none" id="unsubscribereason" name="unsubscribereason" wrap="virtual"></textarea></li>
		<br/>
		<input type="submit" name="submit" value="Unsubscribe" />
		<br/><br/>
		<li>To modify your load restriction email subscription service, click here: <?php echo anchor(resetUrl(curPageURL(), "unsbscrb.php", "p"));?></li>
	</ul>
    <div class="error"></div>
<?php
}

/* Input for resend email screen */
else if ($SCREEN_MODE == 'resend')
{
?>
	<h1 id="subscribe_header1" >Load Restriction Notifications</h1>

	<!-- Placeholders will be populated in the submit process with existing preferences. -->
    <fieldset id='step1'>
        <legend>Step 1 of 1</legend>
		<div id="address">
			<label class="emailLbl" for="email">Enter email address:&nbsp;</label>
			<input class="required emailTxt" id="email" name="email" type="email"/>
		</div>
	    <input type="submit" name="submit" class="finish" style="margin-top:1em" value="Submit" />
	</fieldset>
	<div class="validation-error"></div>
<?php
}
?>