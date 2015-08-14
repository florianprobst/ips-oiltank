<?
/**
* oil tank class
*
* This class represents one oil tank. It logs the oil levels, analyses the consumption, manages all necessary
* all oil tank variables and variable profiles (if necessary)
*
* Note: This script does only fit 
This class supports "normal" tanks only, which means that the oil-level in centimeters is proportional
* to the liters inside the tank.
*.
*
* @link https://github.com/florianprobst/ips-oiltank project website
*
* @author Florian Probst <florian.probst@gmx.de>
*
* @license GNU
* GNU General Public License, version 3
*/

require_once 'VariableManagement/OilTankVariable.class.php';
require_once 'VariableManagement/OilTankVariableProfile.class.php';

/**
* class OilTank
*
* @uses IPowerMeter as power meter interface
*/
class OilTank{
	/**
	* parent object id for all variables created by this script
	*
	* @var integer
	* @access private
	*/
	private $parentId;

	/**
	* variable name prefix to identify variables and variable profiles created by this script
	*
	* @var string
	* @access private
	*/
	private $prefix;

	/**
	* debug: enables / disables debug information
	*
	* @var boolean
	* @access private
	*/
	private $debug;

	/**
	* array of all energymanager variable profiles
	*
	* @var EnergyVariableProfile
	* @access private
	*/
	private $variableProfiles = array();

	/**
	* instance id of the archive control (usually located in IPS\core)
	*
	* @var integer
	* @access private
	*/
	private $archiveId;
	
	/**
	* instance id of tank sensor data.
	* measured distance in cm (using a proJET LevelJET ST
	*
	* @var int
	* @access private
	*/
	private $sensorId;
	
	/**
	* pricing of 1 liter oil
	*
	* @var float
	* @access private
	*/
	private $price_per_liter;
	
	/**
	* instance id of the oil level absolute (in liters)
	*
	* @var OilTankVariable
	* @access private
	*/
	private $oil_level_abs;
	
	/**
	* instance id of the oil level relative (in liters)
	*
	* @var OilTankVariable
	* @access private
	*/
	private $oil_level_rel;
	
	/**
	* maximum filling height in centimeters
	*
	* oil from tank ground to the given height in cm means the tank is at maximum capacity
	*
	* @var integer
	* @access private
	*/
	private $max_filling_height;
	
	/**
	* tank capacity
	*
	* the tanks capacity in liters
	* caution: many tanks are not allowed to be filled up to 100%. usually it's a 95% quote.
	* this means a 3300 liters tank does allow 3130 liters as max capacity
	*
	* @var integer
	* @access private
	*/
	private $capacity;
	
	/**
	* sensor gap
	*
	* distance of the sensor to the oil level at a 100% capacity in centimeters
	* note: see capacity comments about the possible 95% quote
	*
	* @var integer
	* @access private
	*/
	private $sensor_gap;
	
	/**
	* statistics variable: contains html to present the statistics and data of the oil tank
	* maybe deprecated...
	*
	* @var string
	* @access private
	*/
	//private $statistics;

	/**
	* IPS - datatype boolean
	* @const tBOOL
	* @access private
	*/
	const tBOOL = 0;

	/**
	* IPS - datatype integer
	* @const tINT
	* @access private
	*/
	const tINT = 1;

	/**
	* IPS - datatype float
	* @const tFLOAT
	* @access private
	*/
	const tFLOAT = 2;

	/**
	* IPS - datatype string
	* @const tSTRING
	* @access private
	*/
	const tSTRING = 3;
	
	/*
	* color codes for variable profile associations
	* from bad (red) to good (green)
	*/
	const hColor1			= 0xFF0000;	//red
	const hColor2			= 0xFF9D00;	//orange
	const hColor3			= 0xFFF700;	//yellow
	const hColor4			= 0x9DFF00;	//light green
	const hColor5			= 0x46F700;	//green

	/**
	* Constructor
	*
	* @param integer $parentId set the parent object for all items this script creates
	* @param integer $archiveId instance id of the archive control (usually located in IPS\core)
	* @param string $prefix the variable name prefix to identify variables and variable profiles created by this script
	* @param boolean $debug enables / disables debug information
	* @access public
	*/
	public function __construct($parentId, $sensorId, $archiveId, $price_per_liter, $max_filling_height, $capacity, $sensor_gap, $prefix = "OT_", $debug = false){
		$this->parentId = $parentId;
		$this->sensorId = $sensorId;
		$this->archiveId = $archiveId;
		$this->price_per_liter = $price_per_liter;
		$this->max_filling_height = $max_filling_height;
		$this->capacity = $capacity;
		$this->sensor_gap = $sensor_gap;
		$this->debug = $debug;
		$this->prefix = $prefix;
		
		//create variable profiles if they do not exist
		$assoc[0] = ["val"=>0,	"name"=>"%.1f",	"icon" => "", "color" => self::hColor5];
		$assoc[1] = ["val"=>20,	"name"=>"%.1f",	"icon" => "", "color" => self::hColor4];
		$assoc[2] = ["val"=>40,	"name"=>"%.1f",	"icon" => "", "color" => self::hColor3];
		$assoc[3] = ["val"=>60,	"name"=>"%.1f",	"icon" => "", "color" => self::hColor2];
		$assoc[4] = ["val"=>80,	"name"=>"%.1f",	"icon" => "", "color" => self::hColor1];
		array_push($this->variableProfiles, new OilTankVariableProfile($this->prefix . "oil_level_relative", self::tFLOAT, "", " %", $assoc, $this->debug));
		unset($assoc);
		
		array_push($this->variableProfiles, new OilTankVariableProfile($this->prefix . "oil_level_absolute", self::tFLOAT, "", " Liter", NULL, $this->debug));
		
		//create variables if they do not exist
		$this->oil_level_abs = new OilTankVariable($this->prefix . "Oil_Level_Absolute", self::tFLOAT, $this->parentId, $this->variableProfiles[1], true, $this->archiveId, $this->debug);
		$this->oil_level_rel = new OilTankVariable($this->prefix . "Oil_Level_Relative", self::tFLOAT, $this->parentId, $this->variableProfiles[0], true, $this->archiveId, $this->debug);
	}
	
	/**
	* calculateOilLevelCm
	*
	* @param float $distance measured distance from sensor to oil level in tank
	* @return float oil level from tank bottom in centimeters
	* @access private
	*/
	private function calculateOilLevelCm($distance){
		return $this->max_filling_height - $distance + $this->sensor_gap;
	}
	
	/**
	* calculateOilLevelLiters
	*
	* @param float $distance measured distance from sensor to oil level in tank
	* @return float oil level in liters
	* @access private
	*/
	private function calculateOilLevelLiters($distance){
		return round($this->calculateLitersPerCm() * $this->calculateOilLevelCm($distance), 2);
	}
	
	/**
	* calculateLitersPerCm
	*
	* based on the tank data, each oil level cm means a specific amout of oil in liters
	*
	* @return float oil liters per cm
	* @access private
	*/
	private function calculateLitersPerCm(){
		return round($this->capacity / $this->max_filling_height, 2);
	}
	
	/**
	* calculateOilLevelInPercent
	*
	* @param float $distance measured distance from sensor to oil level in tank
	* @return float oil level in percent
	* @access private
	*/
	private function calculateOilLevelInPercent($distance){
		return round(($this->calculateOilLevelLiters($distance) / $this->capacity) * 100, 2);
	}
	
	/**
	* update: read new sensor value and update oil levels, statistics, etc.
	*
	* @access public
	*/
	public function update(){
		$distance = GetValue($this->sensorId);
		$this->oil_level_abs->setValue($this->calculateOilLevelLiters($distance));
		$this->oil_level_rel->setValue($this->calculateOilLevelInPercent($distance));
	}
}
?>