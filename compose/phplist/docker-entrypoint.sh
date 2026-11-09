#!/bin/bash

echo Initialising phpList, Please wait

## wait for the DB container, but not forever
UNCONNECTED=$(phplist | grep "Cannot connect")
COUNT=1
while [[ "$UNCONNECTED" ]] && [[ $COUNT -lt 11 ]] ; do
    echo Waiting for the Database to be available - $COUNT/10
    sleep 10
    UNCONNECTED=$(phplist | grep "Cannot connect")
    COUNT=$(( $COUNT + 1 ))
done

if [[ "$UNCONNECTED" ]]; then
    echo Failed to find a Database to connect to
    exit 1
fi

/usr/bin/phplist -pinitialise
/usr/bin/phplist -pinitlanguages

echo $(phplist --version) READY
exec /usr/sbin/apache2ctl -D FOREGROUND