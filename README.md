# ips-oiltank
IP-Symcon script to analyse oil tank levels and oil usage

# ips-speedport
IP-Symcon Skript zur Datenerfassung und Auswertung von �ltanks.

[![Release](https://img.shields.io/github/release/florianprobst/ips-oiltank.svg?style=flat-square)](https://github.com/florianprobst/ips-oiltank/releases/latest)
[![License](https://img.shields.io/badge/license-LGPLv3-brightgreen.svg?style=flat-square)](https://github.com/florianprobst/ips-oiltank/blob/master/LICENSE)

## Aufgabe des Skripts
Dieses Skript liest von einem �lpegelsensor Daten aus und ermittelt damit den aktuellen �lstand in Litern und prozentual aus.
Dabei gilt die Annahme, dass der �lpegelsensor von Oben die Distanz zwischen dem Sensor und dem �lpegel im Tank in Zentimetern ausgibt und
der �ltank �ber eine proportionale Literzahl je Zentimeter F�llh�he verf�gt. 
(In der aktuellen Version sind beispielsweise keine kugelf�rmigen Tanks unterst�tzt)

## Unterst�tzte Sensoren
* ProJET LevelJET ST, angebunden �ber einen ProJET JKM-868 (LevelJET) Modul

## Weiterf�hrende Informationen
Das Skript legt selbstst�ndig ben�tigte IPS-Variablen und Variablenprofile an.
Derzeit sind dies 2-Variablen und 2 Variablenprofile. (Je nach IP-Symcon Lizenz bitte ber�cksichtigen)
Durch das Speichern der Werte in IPS-Variablen wird Logging und das Anbinden von IPS-Events erm�glicht.
Zur besseren Auffindbarkeit und eindeutigen Zuordnung werden alle Variablenprofile mit einem Pr�fix angelegt. 
Standardm�ssig lautet das `OT_`.

## Installation

1. Dieses Repository im IP-Symcon Unterordner `webfront/user/` klonen. Bsp.: `C:\IP-Symcon\webfront\user\ips-oiltank` oder alternativ als zip-Datei herunterladen und in den `IP-Symcon/webfront/user` Unterordner entpacken.
2. In der IP-Symcon Verwaltungskonsole eine Kategorie `OilTank` und eine Unterkategorie `Variables` erstellen (Namen und Ablageorte sind frei w�hlbar)
3. Unterhalb der Kategorie `OilTank` sind mehrere Skripte manuell anzulegen. Die anzulegenden Skripte befinden sich im Unterordner `ips-scripts` und k�nnen per copy&paste in die IPS-Console eingetragen werden. Alternativ sind die Skripte auch weiter unten direkt beschrieben.

#### Struktur in der IP-Symcon Console nach Installation
(siehe dazu auch Screenshot unten)
* OilTank (Kategorie)
* Variables (Kategorie)
* - diverse automatisch generierte Variablen nach erstem Ausf�hren
* Config (script)
* Update (script)

## IP-Symcon Console - anzulegende Skripte
###config script
Enth�lt die "globale" Konfiguration der Speedport-Anbindung und wird von den anderen IPS-Speedport-Scripten aufgerufen.
```php
<?
require_once("../webfront/user/ips-oiltank/OilTank.class.php");

$parentId = 24854 /*[System\Skripte\OilTank\Variables]*/; //Ablageort f�r erstellte Variablen
$sensorId = 17807 /*[Hardware\Keller\Heiz�lkeller\JKM-868 (LevelJET)\Distance]*/;   //sensordaten welche den gemessenen �lpegel in cm angeben
$archiveId= 18531 /*[Archiv]*/; //Instanz ID des IPS-Archivs in welchem die Werte des �ltanks geloggt werden sollen.
$preis_pro_liter = 0.6027; // Preis pro Liter Heiz�l deines Anbieters
$max_fuellhohe = 120; //maximale f�llh�he des beh�lters in cm
$sensor_abstand = 16; //Abstand des Sensors zum �l-pegel eines maximal bef�llten beh�lters
$max_tank_inhalt = 3130; //maximale f�llmenge bei zul�ssiger 95% f�llung des beh�lters (in meinem fall 95% meines 3300 liter tanks)
$debug = true;
$prefix = "OT_";

//ab hier nichts �ndern
$oiltank = new OilTank($parentId, $sensorId, $archiveId, $preis_pro_liter, $max_fuellhohe, $max_tank_inhalt, $sensor_abstand, $prefix, $debug);
?>
```

###update status script
Sammelt alle Statusinformationen, Anruferlisten, etc. und legt diese in den daf�r vorgesehenen IPS Variablen ab.
Mit dem ersten manuellen Ausf�hren legt das Skript automatisch ein Event an um sich k�nftig im, in der Konfiguration angegebenen
Interval k�nftig selbst auszf�hren (bsp. alle 10 Minuten)
```php
<?
$config_script = 14977 /*[System\Skripte\OilTank\Config]*/; //instanz id des ip-symcon config skripts

require_once(IPS_GetScript($config_script)['ScriptFile']);

$oiltank->update();
?>
```

