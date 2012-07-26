@package: Salat Reminder
@authour: Karl Holz
@link: http://www.salamcast.com/demos/SalatCast/

July 25, 2012

Asalam Alikum Brothers and Sisters, Salah is a very important part of EVERY Muslims daily life and
it is an obligation to perform this act of worship on time five times everyday.  Salat Reminder is just
another tool to help remind us to remember Allah Subhannah Wa'Tallah while living in the west with our
busy schedules.  In Canada I don't have the luxury of hearing the Azan being called out loud in our streets
everyday, like in many Muslim countries;  This adds to the challenge of living Islam and being a good Muslim,
since we are not always in the company of our brothers and sisters in Islam.  

Salat Reminder is a simple ATOM feed generator that displays todays Salat timings based on your ip addresses
geographical location and are sourced from Islamic Finders Prayer Web service. The default options can be
configured in the config.ini file;  I've added some comments to help you configure it to your needs. 

Please note that if you're using a proxy of any kind your results will most likely be incorrect.  For example,
the Mc Donald’s wifi I'm using in Toronto, Canada is piped through Washington DC in the USA.  The title of the
feed will have the current city and country; if you're in doubt, look up the timings from a source that matches
your location.


------------------------------------------

Dependancies:

ip2locationlite.class:

http://ipinfodb.com/ip_location_api.php

Universal Feed Generator:

http://www.phpclasses.org/package/4427-PHP-Generate-feeds-in-RSS-1-0-2-0-an-Atom-formats.html

------------------------------------------

Web Services used:

------------------------------------------

IP Info DB:
http://ipinfodb.com/

It is very important that you get a free API key for ipinfodb and copy and paste it into a file called
".ht_ipinfodb.ini" (the .ht is to prevent the file from being viewed on the web, as per default apache
httpd config), this is a basic template, just replace the text in key with the real key:

;-----------------------------------------
; Example of .ht_ipinfodb.ini
;-----------------------------------------
[api]
key="<api key |ljdslfddfsldfhjdfsjhdfs>"
;-----------------------------------------

Earth Tools:

http://www.earthtools.org/webservices.htm

-----------------------------------------

IslamicFinder.org:

http://www.islamicfinder.org/prayerService.php?timezone=-5.0&dayLight=1&pmethod=5&fajrTwilight1=
        &ishaTwilight=0&fajrTwilight2=&ishaInterval=0&HanfiShafi=1&dhuhrInterval=1&maghribInterval=1
        &city=toronto&state=ON&zipcode=&country=canada&calculate=1&lang=english#xml_link

_________________________________________


Please download this and run it on your own web server, this will help keep the feed generating nicely.
EarthTools and IP Info DB are free services and shouldn't be abused by your scripts,  Inshallah my
scripts don't.

If you notice any mistakes in my readme or code or calculations, please feel free to let me know. 