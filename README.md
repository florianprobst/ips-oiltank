# ips-oiltank
IP-Symcon script to analyse oil tank levels and oil usage

# ips-speedport
IP-Symcon Skript zur Datenerfassung und Auswertung von Öltanks.

[![Release](https://img.shields.io/github/release/florianprobst/ips-oiltank.svg?style=flat-square)](https://github.com/florianprobst/ips-oiltank/releases/latest)
[![License](https://img.shields.io/badge/license-LGPLv3-brightgreen.svg?style=flat-square)](https://github.com/florianprobst/ips-oiltank/blob/master/LICENSE)

## Aufgabe des Skripts
Dieses Skript liest von einem Ölpegelsensor Daten aus und ermittelt damit den aktuellen Ölstand in Litern und prozentual aus.
Dabei gilt die Annahme, dass der Ölpegelsensor von Oben die Distanz zwischen dem Sensor und dem Ölpegel im Tank in Zentimetern ausgibt und
der Öltank über eine proportionale Literzahl je Zentimeter Füllhöhe verfügt. 
(In der aktuellen Version sind beispielsweise keine kugelförmigen Tanks unterstützt)

## Unterstützte Sensoren
* ProJET LevelJET ST, angebunden über einen ProJET JKM-868 (LevelJET) Modul

## Weiterführende Informationen
Das Skript legt selbstständig benötigte IPS-Variablen und Variablenprofile an.
Derzeit sind dies 2-Variablen und 2 Variablenprofile. (Je nach IP-Symcon Lizenz bitte berücksichtigen)
Durch das Speichern der Werte in IPS-Variablen wird Logging und das Anbinden von IPS-Events ermöglicht.
Zur besseren Auffindbarkeit und eindeutigen Zuordnung werden alle Variablenprofile mit einem Präfix angelegt. 
Standardmässig lautet das `OT_`.

## Installation

1. Dieses Repository im IP-Symcon Unterordner `webfront/user/` klonen. Bsp.: `C:\IP-Symcon\webfront\user\ips-oiltank` oder alternativ als zip-Datei herunterladen und in den `IP-Symcon/webfront/user` Unterordner entpacken.
2. In der IP-Symcon Verwaltungskonsole eine Kategorie `OilTank` und eine Unterkategorie `Variables` erstellen (Namen und Ablageorte sind frei wählbar)
3. Unterhalb der Kategorie `OilTank` sind mehrere Skripte manuell anzulegen. Die anzulegenden Skripte befinden sich im Unterordner `ips-scripts` und können per copy&paste in die IPS-Console eingetragen werden. Alternativ sind die Skripte auch weiter unten direkt beschrieben.

#### Struktur in der IP-Symcon Console nach Installation
(siehe dazu auch Screenshot unten)
* OilTank (Kategorie)
* Variables (Kategorie)
* - diverse automatisch generierte Variablen nach erstem Ausführen
* Config (script)
* Update (script)

## IP-Symcon Console - anzulegende Skripte
###config script
Enthält die "globale" Konfiguration der Speedport-Anbindung und wird von den anderen IPS-Speedport-Scripten aufgerufen.
```php
<?
require_once("../webfront/user/ips-oiltank/OilTank.class.php");

$parentId = 24854 /*[System\Skripte\OilTank\Variables]*/; //Ablageort für erstellte Variablen
$sensorId = 17807 /*[Hardware\Keller\Heizölkeller\JKM-868 (LevelJET)\Distance]*/;   //sensordaten welche den gemessenen Ölpegel in cm angeben
$archiveId= 18531 /*[Archiv]*/; //Instanz ID des IPS-Archivs in welchem die Werte des Öltanks geloggt werden sollen.
$preis_pro_liter = 0.6027; // Preis pro Liter Heizöl deines Anbieters
$max_fuellhohe = 120; //maximale füllhöhe des behälters in cm
$sensor_abstand = 16; //Abstand des Sensors zum öl-pegel eines maximal befüllten behälters
$max_tank_inhalt = 3130; //maximale füllmenge bei zulässiger 95% füllung des behälters (in meinem fall 95% meines 3300 liter tanks)
$debug = true;
$prefix = "OT_";

//ab hier nichts ändern
$oiltank = new OilTank($parentId, $sensorId, $archiveId, $preis_pro_liter, $max_fuellhohe, $max_tank_inhalt, $sensor_abstand, $prefix, $debug);
?>
```

###update status script
Sammelt alle Statusinformationen, Anruferlisten, etc. und legt diese in den dafür vorgesehenen IPS Variablen ab.
Mit dem ersten manuellen Ausführen legt das Skript automatisch ein Event an um sich künftig im, in der Konfiguration angegebenen
Interval künftig selbst auszführen (bsp. alle 10 Minuten)
```php
<?
$config_script = 14977 /*[System\Skripte\OilTank\Config]*/; //instanz id des ip-symcon config skripts

require_once(IPS_GetScript($config_script)['ScriptFile']);

$oiltank->update();
?>
```

