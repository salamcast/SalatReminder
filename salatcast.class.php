<?php

include('includes/ip2locationlite.class.php');
include("includes/FeedWriter.php");

/**
 * @package: Salat Reminder
 * @authour: Karl Holz
 * @link: http://www.salamcast.com/demos/SalatCast/
 *
 * Copyright (c) July 2012 Karl Holz <newaeon -at- mac -dot- com>
 * 
 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License
 as published by the Free Software Foundation; either version 2
 of the License, or (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 */


class SalatReminder {
    private $config;
    private $azan;
    private $info=array();
    function __construct(){
        if (!is_dir('cache')) mkdir('cache');
        $config_full=parse_ini_file('./config.ini', TRUE);
        $this->config=array_shift($config_full);
        $this->info=$config_full;
        $this->user=$_SERVER['REMOTE_ADDR'];
        //geo support
        if (is_file('./.ht_ipinfodb.ini')) {
            $a=parse_ini_file('.ht_ipinfodb.ini');
            $api=$a['key'];
            //Load the class
            $ipLite = new ip2location_lite;
            $ipLite->setKey($api);
            //Get errors and locations
            $locations = $ipLite->getCity($this->user);
            $this->errors = $ipLite->getError();
            if (!empty($locations) && is_array($locations)) {
                foreach ($locations as $field => $val) {
                    switch ($field) {
                        case 'countryCode':// countryCode : CA
                            $this->initals=$val;
                        break; 
                        case 'countryName'://countryName : CANADA
                            $this->country=$val;
                        break; 
                        case 'regionName'://regionName : ONTARIO
                            $this->state=$val;
                        break; 
                        case 'cityName'://cityName : NORTH YORK
                            $this->city=$val;
                        break; 
                        case 'zipCode'://zipCode : 
                            $this->zipcode=$val;
                        break; 
                        case 'latitude'://latitude :
                            $this->latitude=$val;
                        break; 
                        case 'longitude'://longitude : 
                            $this->longitude=$val;
                        break; 
                        case 'timeZone'://timeZone : -04:00
                            $this->timezone=$val;
                            // set current Etc/GMT time zone for current request
                            date_default_timezone_set("Etc/GMT".str_replace(array('-0', ':00'), array('+', ''), $val));
                        break; 
                    }
                }
            }
        } else {
            die("You need to have a file called ./.ht_ipinfodb.ini, with the line<br/><br />[api]<br />key=<ipinfodb api key>");
        }

        $this->azan=parse_ini_string($this->make_ini());
    }
    
    function __destruct() { }
    
    function __get($value) {
        if (array_key_exists($value, $this->config)) {
            return $this->config[$value];
        }
        return; // return blank
    }
    
    function __set($name, $value) {
        if (is_string($value) || is_numeric($value)) {
            $this->config[$name]=$value;
            return TRUE;
        }
        return FALSE;        
    }
    
    function get_link() {
        $get="http://www.islamicfinder.org/prayer_service.php?";
        $get.="country=".urlencode($this->country);    
        $get.="&city=".urlencode($this->city);
        $get.="&state=".urlencode($this->state);
        $get.="&zipcode=".urlencode($this->zipcode);
        $get.="&latitude=".$this->latitude;
        $get.="&longitude=".$this->longitude;
        $get.="&timezone=".$this->get_real_timezone();// required
        $get.="&HanfiShafi=".$this->HanfiShafi;
        $get.="&pmethod=".$this->pmethod;
        if ($this->fajrTwilight1) $get.="&fajrTwilight=".$this->fajrTwilight1;
        if ($this->fajrTwilight2) $get.="&fajrTwilight=".$this->fajrTwilight2;
        if ($this->ishaTwilight) $get.="&ishaTwilight=".$this->ishaTwilight;
        if ($this->ishaInterval) $get.="&ishaInterval=".$this->ishaInterval;
        if ($this->dhuhrInterval) $get.="&dhuhrInterval=".$this->dhuhrInterval;
        if ($this->maghribInterval) $get.="&maghribInterval=".$this->maghribInterval;
        if ($this->dayLight) $get.="&dayLight=".$this->dayLight;
        $get.="&simpleFormat=xml";
        return $get;
    }
    
    function get_xml() {
        return $this->get_from_url($this->get_link(), '-6 hours');
    }
    
    function get_from_url($url, $time='-24 hours') {
        $file='cache/.ht_'.md5($url).".xml";

        if (is_file($file) && filectime($file) > strtotime($time) ) {
            return file_get_contents($file);
        }
        $ch = curl_init(); 
        curl_setopt($ch, CURLOPT_URL, $url); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // mimic users web browser
        curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER["HTTP_USER_AGENT"]); 
        $xml = curl_exec($ch);
        curl_close($ch);
        file_put_contents($file, $xml);
        return $xml;
    }
    

     /**
      * make_ini()
      *
      * @return string $xslproc->transformToXml($xml) transformed XML data
      */
    function make_ini() {
        $xsltmpl=<<<I
<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform"  version="1.0">
 <xsl:output method="text"/>
 <xsl:template match="/"><xsl:apply-templates select="prayer" />
 </xsl:template>
 <xsl:template match="prayer">[prayer]
country="<xsl:value-of select="country"/>"
city="<xsl:value-of select="city"/>"
today="<xsl:value-of select="date"/>"
today_islamic="<xsl:value-of select="hijri"/>"
fajr="<xsl:value-of select="normalize-space(fajr)"/>"
sunrise="<xsl:value-of select="normalize-space(sunrise)"/>"
dhuhr="<xsl:value-of select="normalize-space(dhuhr)"/>"
asr="<xsl:value-of select="normalize-space(asr)"/>"
maghrib="<xsl:value-of select="normalize-space(maghrib)"/>"
isha="<xsl:value-of select="normalize-space(isha)"/>"</xsl:template>
</xsl:stylesheet>
I
        ;
        $xml_load=$this->get_xml();
        if ($xml_load == '') die('Service Failed to get your locations Salat timings');
        $xml=new DOMDocument();
        $xml->loadXML($xml_load);
        //loads XSL template file
        $xsl=new DOMDocument();
        $xsl->loadXML($xsltmpl);
        //process XML and XSLT files and return result
        $xslproc = new XSLTProcessor();
        $xslproc->importStylesheet($xsl);
        return $xslproc->transformToXml($xml);
    } 

    function get_real_timezone() {
        $url="http://www.earthtools.org/timezone-1.1/".$this->latitude."/".$this->longitude;
        $xml_load=$this->get_from_url($url, '- 48 hours');
        if ($xml_load == '') die('Service Failed to get your timezone');
        $xsltmpl=<<<E
<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform"   version="1.0">
 <xsl:output method="text"/>
 <xsl:template match="/"><xsl:value-of select="/timezone/offset"/></xsl:template>
</xsl:stylesheet>
E
        ;
        $xml=new DOMDocument();
        $xml->loadXML($xml_load);
        //loads XSL template file
        $xsl=new DOMDocument();
        $xsl->loadXML($xsltmpl);
        //process XML and XSLT files and return result
        $xslproc = new XSLTProcessor();
        $xslproc->importStylesheet($xsl);
        return $xslproc->transformToXml($xml);        
    }
    private $salat=array();
    private $salat_am=array('fajr', "sunrise");
    private $salat_pm=array("dhuhr", "asr", "maghrib", "isha");
    /**
     * salat check, checks for the next salat time and current salat time.
     * because islamic finder uses 12 hour time format, i'm going to have to do some check for morning and night,
     * fajr in the AM
     * dhuhr, asr, maghrib and isha in the PM 
     */
    function salat_check() {
        $now=date('g:i A');
        $salat=array();
        $past=array();
        $next=array();
        foreach ($this->salat_am as $s) {
            $datetime1 = new DateTime($now);
            $datetime2 = new DateTime($this->azan[$s]." AM");
            $interval = $datetime1->diff($datetime2);
            $this->salat[$s]=array(
                'title' =>  ucfirst($s)." - ".$this->azan[$s]." AM",
                'hours' => $interval->format("%h"),
                'mins' => $interval->format("%i"),
                'time' => $this->azan[$s]." AM",
                'now'  => $now,
                'today' => $this->azan['today'],
                'islamic_day' => $this->azan['today_islamic']
            );
            $R=$interval->format("%R");
            if ($R == '-') { $this->salat[$s]['status']='past'; } else {
                $next[]=$interval->format(ucfirst($s)." will start in %h hours and %i minutes ");
                $this->salat[$s]['status']='later';
            }
        }
        foreach ($this->salat_pm as $s) {
            $datetime1 = new DateTime($now);
            $datetime2 = new DateTime($this->azan[$s]." PM");
            $interval = $datetime1->diff($datetime2);
            $this->salat[$s]=array(
                'title' =>  ucfirst($s)." - ".$this->azan[$s]." PM",
                'hours' => $interval->format("%h"),
                'mins' => $interval->format("%i"),
                'time' => $this->azan[$s]." PM",
                'now'  => $now,
                'today' => $this->azan['today'],
                'islamic_day' => $this->azan['today_islamic']
            );
            $R=$interval->format("%R");
            if ($R == '-') { $this->salat[$s]['status']='past'; } else {
                $next[]=$interval->format(ucfirst($s)." will start in %h hours and %i minutes \n");
                $this->salat[$s]['status']='later';
            }
        }
        $this->next=array_shift($next);
        if ($this->next == '') {
            if (date('A') == 'AM') {
                $datetime1 = new DateTime($now);
                $datetime2 = new DateTime(strtotime("today at ".$this->azan['fajr']." AM", strtotime($this->azan['today'])));
                $interval = $datetime1->diff($datetime2);
                $this->next=$interval->format("Fajr is starting in %h hours and %i minutes \n");
            } else {
                $this->next="Fajr will start aproximently at ".$this->azan['fajr']." AM";
            }
        }
    }
    
    function __toString() {
        if (count($this->salat) < 1) $this->salat_check();
        $TestFeed = new FeedWriter(ATOM);
	$TestFeed->setTitle('Salat Reminder | '.$this->azan['today_islamic']." - ".$this->azan['today']." | ".$this->city.", ".$this->state.", ".$this->country);
	$TestFeed->setLink('http://www.salamcast.com'.$_SERVER['REQUEST_URI']);
	//For other channel elements, use setChannelElement() function
	$TestFeed->setChannelElement('updated', date(DATE_ATOM , time()));
	$TestFeed->setChannelElement('author', array('name'=>'IslamFinder .org'));
        $newItem = $TestFeed->createNewItem();
        // set current salat time
        $newItem->setTitle("Next Salat comming up: ".$this->next);
        $newItem->setLink('http://www.salamcast.com'.$_SERVER['REQUEST_URI'].'?'.md5($this->get_link()));//->setLink($this->get_link());
        $newItem->setDate(time());

//        $desc='';
        foreach ($this->salat as $k => $s) {
            $desc.="<h3>".ucfirst($s['title'])."</h3>";
            $desc.="<p>".$this->info[$k]['text']."</p>";
            if ($this->info[$k]['sunnah1'] > 0)  $desc.="<p><strong>Sunnah before Fardh</strong>: ".$this->info[$k]['sunnah1']." Raka'as</p>";
            if ($this->info[$k]['fardh'] > 0)    $desc.="<p><strong>Fardh: </strong>".$this->info[$k]['fardh']." Raka'as </p>";
            if ($this->info[$k]['sunnah2'] > 0)  $desc.="<p><strong>Sunnah after Fardh:</strong> ".$this->info[$k]['sunnah2']." Raka'as</p>";
            if ($this->info[$k]['witr'] > 0)     $desc.="<p><strong>Witr:</strong> ".$this->info[$k]['sunnah2']." Raka'as</p>";
        }
        $newItem->setDescription($desc);
        $TestFeed->addItem($newItem);
	$TestFeed->genarateFeed();
        exit();
    }
}
?>