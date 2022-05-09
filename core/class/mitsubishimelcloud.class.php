<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

/* * ***************************Includes********************************* */
require_once __DIR__  . '/../../../../core/php/core.inc.php';

class mitsubishimelcloud extends eqLogic
{
  /*     * *************************Attributs****************************** */

  /*
   * Permet de définir les possibilités de personnalisation du widget (en cas d'utilisation de la fonction 'toHtml' par exemple)
   * Tableau multidimensionnel - exemple: array('custom' => true, 'custom::layout' => false)
   public static $_widgetPossibility = array();
   */

  /*     * ***********************Methode static*************************** */
  /** Get token from MELCloud*/
  public static function GetToken() {
    $Email = config::byKey('Email', 'mitsubishimelcloud');
    $Password = config::byKey('Password', 'mitsubishimelcloud');
    $Language = config::byKey('Language', 'mitsubishimelcloud');
    $AppVersion = config::byKey('AppVersion', 'mitsubishimelcloud');

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://app.melcloud.com/Mitsubishi.Wifi.Client/Login/ClientLogin');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt(
      $ch,
      CURLOPT_POSTFIELDS,
      'Email=' . $Email . '&Password=' . $Password . '&Language=' . $Language . '&AppVersion=' . $AppVersion . '&Persist=true&CaptchaChallenge=null&CaptchaChallenge=null'
    );
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $server_output = curl_exec($ch);
    curl_close($ch);

    $json = json_decode($server_output, true);

    if($json['ErrorId'] == null) {
      log::add(__CLASS__, 'debug', 'Login OK.');
      config::save('Token', $json['LoginData']['ContextKey'], 'mitsubishimelcloud');
      ajax::success();
    } elseif ($json['ErrorId'] == 1) {
      config::save('Token', __('Login ERROR : identifiant ou mot de passe MELCloud incorrect', __FILE__), 'mitsubishimelcloud');
      log::add(__CLASS__, 'debug', __('Login ERROR : identifiant ou mot de passe MELCloud incorrect.', __FILE__));
    } else {
      config::save('Token', 'Login ERROR : code n°' . $json['ErrorId'], 'mitsubishimelcloud');
      log::add(__CLASS__, 'debug', 'Login ERROR : code n°' . $json['ErrorId']);
    }
  }

  /** Collect data from MELCloud app */
  public static function SynchronizeMELCloud() {
    $Token = config::byKey('Token', __CLASS__);
    if($Token == '' || substr($Token, 0, 11) == 'Login ERROR') {
      message::add('mitsubishimelcloud', __('Merci de récupérer le token MELCloud avant de créer des équipements.', __FILE__));
      log::add(__CLASS__, 'debug', __('Merci de récupérer le token MELCloud avant de créer des équipements.', __FILE__));
    } else {
      log::add(__CLASS__, 'info', '===== Synchronize MELCloud data =====');
      $request = new com_http('https://app.melcloud.com/Mitsubishi.Wifi.Client/User/ListDevices');
      $request->setHeader(array('X-MitsContextKey: ' . $Token));
      $json = $request->exec(30000, 2);

      $values = json_decode($json, true);
      foreach ($values as $maison) {
        log::add(__CLASS__, 'debug', __('Bâtiment : ', __FILE__) . $maison['Name']);
        for ($i = 0; $i < count($maison['Structure']['Devices']); $i++) {
          $device = $maison['Structure']['Devices'][$i];
          log::add(__CLASS__, 'debug', 'Synchronizing device ' . $i . ' ' . $device['DeviceName']);
          self::SynchronizeCommands($device);
        }
        // FLOORS
        for ($a = 0; $a < count($maison['Structure']['Floors']); $a++) {
          log::add(__CLASS__, 'debug', 'FLOORS ' . $a);
          // AREAS IN FLOORS
          for ($i = 0; $i < count($maison['Structure']['Floors'][$a]['Areas']); $i++) {
            for ($d = 0; $d < count($maison['Structure']['Floors'][$a]['Areas'][$i]['Devices']); $d++) {
              $device = $maison['Structure']['Floors'][$a]['Areas'][$i]['Devices'][$d];
              self::SynchronizeCommands($device);
            }
          }
          // FLOORS
          for ($i = 0; $i < count($maison['Structure']['Floors'][$a]['Devices']); $i++) {
            $device = $maison['Structure']['Floors'][$a]['Devices'][$i];
            self::SynchronizeCommands($device);
          }
        }
        // AREAS
        for ($a = 0; $a < count($maison['Structure']['Areas']); $a++) {
          log::add(__CLASS__, 'info', 'AREAS ' . $a);
          for ($i = 0; $i < count($maison['Structure']['Areas'][$a]['Devices']); $i++) {
            log::add(__CLASS__, 'info', 'machine AREAS ' . $i);
            $device = $maison['Structure']['Areas'][$a]['Devices'][$i];
            self::SynchronizeCommands($device);
          }
        }
      }
    }
  }

  /** Write equipment information from Mitsubishi servers for each equipment */
  public static function SynchronizeCommands($device) {
    log::add(__CLASS__, 'debug', 'Synchronize : ' . $device['DeviceName']);
    if($device['DeviceID'] == '') return;
    log::add(__CLASS__, 'debug', $device['DeviceID'] . ' ' . $device['DeviceName']);

    $theEQlogic = eqLogic::byTypeAndSearchConfiguration(__CLASS__, '"MachineName":"' . $device['DeviceName'] . '"');

    if(count($theEQlogic) == 0) {
      // Create the equipment if it doesn't exist yet
      $mylogical = new melcloud();
      $mylogical->setIsVisible(0);
      $mylogical->setIsEnable(0);
      $mylogical->setEqType_name(__CLASS__);
      $mylogical->setName($device['DeviceName']);
      $mylogical->setConfiguration('MachineName', $device['DeviceName']);
      $mylogical->save();
    } else {
      // Update the equipment if it already exist
      $mylogical =  $theEQlogic[0];
      if($mylogical->getIsEnable()) {
        log::add(__CLASS__, 'debug', 'Set device ' . $device['Device']['DeviceID']);
        $mylogical->setConfiguration('deviceid', $device['Device']['DeviceID']);
        $mylogical->setConfiguration('buildid', $device['BuildingID']);

        if($device['Device']['DeviceType'] == '0') {
          log::add(__CLASS__, 'debug', __('PAC type air/air', __FILE__));
          $mylogical->setConfiguration('typepac', 'air/air');
        } elseif ($device['Device']['DeviceType'] == '1') {
          log::add(__CLASS__, 'debug', __('PAC type air/eau', __FILE__));
          $mylogical->setConfiguration('typepac', 'air/eau');
        } else {
          log::add(__CLASS__, 'error', __('Pas de type de PAC trouvé', __FILE__));
          return;
        }

        if($mylogical->getConfiguration('rubriques') == '') {
            $mylogical->setConfiguration('rubriques', print_r($device['Device'], true));
        }

        $mylogical->save();

        foreach ($mylogical->getCmd() as $cmd) {
          switch ($cmd->getLogicalId()) {
            case 'refresh':
            case 'On':
            case 'Off':
            case 'OperationMode':
            case 'VaneVerticalDirection':
            case 'VaneHorizontalDirection':
              // These commands doesn't need and update
              log::add(__CLASS__, 'debug', 'log : '.$cmd->getLogicalId().__(' : On ne traite pas cette commande', __FILE__));
              break;
            
            case 'SetTemperature_Value':
            case 'OperationMode_Value':
            case 'FanSpeed_Value':
            case 'VaneVerticalDirection_Value':
            case 'VaneHorizontalDirection_Value':
              // We do the same operation for these 5 "xx_Value"
              $operation = str_replace('_Value', '', $cmd->getLogicalId());
              log::add(__CLASS__, 'debug', 'log : '.$cmd->getLogicalId().__(' pour ', __FILE__).$operation.__(' et la valeur ', __FILE__).$device['Device'][$operation]);
              $cmd->setCollectDate('');
              $cmd->event($device['Device'][$operation]);
              $cmd->save();
              break;

            case 'SetTemperature':
              // Define Min / Max temperature for slider AND current requested temperature
              $stepArray = array('step' => floatval($device['Device']['TemperatureIncrement']));
              $cmd->setDisplay('parameters', $stepArray);
              if($device['Device']['OperationMode'] == 1) {
                log::add(__CLASS__, 'debug', __('OperationMode : HEAT', __FILE__));
                log::add(__CLASS__, 'debug', __('definir les temperatures Max / Min : ', __FILE__).intval($device['Device']['MaxTempHeat']).' / '.intval($device['Device']['MinTempHeat']));
                $cmd->setConfiguration('maxValue', intval($device['Device']['MaxTempHeat']));
                $cmd->setConfiguration('minValue', intval($device['Device']['MinTempHeat']));
              } else {
                log::add(__CLASS__, 'debug', __('OperationMode : COOL', __FILE__));
                log::add(__CLASS__, 'debug', __('definir les temperatures Max / Min : ', __FILE__).intval($device['Device']['MaxTempCoolDry']).' / '.intval($device['Device']['MinTempCoolDry']));
                $cmd->setConfiguration('maxValue', intval($device['Device']['MaxTempCoolDry']));
                $cmd->setConfiguration('minValue', intval($device['Device']['MinTempCoolDry']));
              }
              $cmd->event($device['Device'][$cmd->getLogicalId()]);
              $cmd->save();
              break;

            case 'FanSpeed':
              log::add(__CLASS__, 'debug', __('log pour FanSpeed : ', __FILE__).$cmd->getLogicalId().' '.$device['Device']['NumberOfFanSpeeds']);
              $cmd->setConfiguration('maxValue', $device['Device']['NumberOfFanSpeeds']);
              $cmd->save();
              break;

            default:
              // For : Power, RoomTemperature
              log::add(__CLASS__, 'debug','general case : '.$cmd->getLogicalId().' : '.$device['Device'][$cmd->getLogicalId()]);
              $cmd->event($device['Device'][$cmd->getLogicalId()]);
              $cmd->save();
              break;
          }
        }

        // Update
        $mylogical->Refresh();
        $mylogical->toHtml('dashboard');
        $mylogical->refreshWidget();

      }
    }
  }

  /*     * ***********************Methode static*************************** */

  //Fonction exécutée automatiquement toutes les 5 minutes par Jeedom
  public static function cron5() {  }
  

  /*     * *********************Méthodes d'instance************************* */
  /** Method called after saving your Jeedom equipment */
  public function postSave() {
    if($this->getConfiguration('deviceid') == ''){
      self::SynchronizeMELCloud();
      if($this->getConfiguration('deviceid') == '') return;
    }

    $RefreshCmd = $this->getCmd(null, 'refresh');
    if($this->getConfiguration('deviceid') != '' && !is_object($RefreshCmd)) {
      // Create common commande for both style :
      $refresh = $this->getCmd(null, 'refresh');
      if(!is_object($refresh)) {
        $refresh = (new mitsubishimelcloudCmd)
        ->setName(__('Actualiser', __FILE__))
        ->setLogicalId('refresh')
        ->setOrder(1)
        ->setIsVisible(1)
        ->setType('action')
        ->setSubType('other')
        ->setEqLogic_id($this->getId());
        $refresh->save();
      }

      $PowerState = $this->getCmd(null, 'Power');
      if(!is_object($PowerState)) {
        $PowerState = (new mitsubishimelcloudCmd)
        ->setName(__('Power', __FILE__))
        ->setLogicalId('Power')
        ->setOrder(2)
        ->setIsVisible(0)
        ->setIsHistorized(0)
        ->setType('info')
        ->setSubType('binary')
        ->setGeneric_type('ENERGY_STATE')
        ->setEqLogic_id($this->getId());
        $PowerState->save();
      }

      $On = $this->getCmd(null, 'On');
      if(!is_object($On)) {
        $On = (new mitsubishimelcloudCmd)
        ->setName(__('On', __FILE__))
        ->setLogicalId('On')
        ->setOrder(3)
        ->setIsVisible(1)
        ->setIsHistorized(0)
        ->setType('action')
        ->setSubType('other')
        ->setTemplate('dashboard', 'OnOffMitsubishi')
        ->setTemplate('mobile', 'OnOffMitsubishi')
        ->setDisplay('generic_type', 'ENERGY_ON')
        ->setConfiguration('updateCmdId', $PowerState->getEqLogic_id())
        ->setConfiguration('updateCmdToValue', 1)
        ->setEqLogic_id($this->getId());
        $On->save();
      }

      $Off = $this->getCmd(null, 'Off');
      if(!is_object($Off)) {
        $Off = (new mitsubishimelcloudCmd)
        ->setName(__('Off', __FILE__))
        ->setLogicalId('Off')
        ->setOrder(4)
        ->setIsVisible(1)
        ->setIsHistorized(0)
        ->setType('action')
        ->setSubType('other')
        ->setTemplate('dashboard', 'OnOffMitsubishi')
        ->setTemplate('mobile', 'OnOffMitsubishi')
        ->setDisplay('generic_type', 'ENERGY_OFF')
        ->setConfiguration('updateCmdId', $PowerState->getEqLogic_id())
        ->setConfiguration('updateCmdToValue', 0)
        ->setEqLogic_id($this->getId());
        $Off->save();
      }

      // Create command specific of each style :
      if($this->getConfiguration('typepac') == 'air/air'){
        $RoomTemperature = $this->getCmd(null, 'RoomTemperature');
        if(!is_object($RoomTemperature)) {
          $RoomTemperature = (new mitsubishimelcloudCmd)
          ->setName(__('Température de la pièce', __FILE__))
          ->setLogicalId('RoomTemperature')
          ->setOrder(5)
          ->setIsVisible(1)
          ->setIsHistorized(1)
          ->setType('info')
          ->setSubType('numeric')
          ->setUnite('°C')
          ->setTemplate('dashboard', 'TemperatureMitsubishi')
          ->setTemplate('mobile', 'TemperatureMitsubishi')
          ->setDisplay('generic_type', 'THERMOSTAT_TEMPERATURE')
          ->setEqLogic_id($this->getId());
          $RoomTemperature->save();
        }
        
        $SetTemperature_Value = $this->getCmd(null, 'SetTemperature_Value');
        if(!is_object($SetTemperature_Value)) {
          $SetTemperature_Value = (new mitsubishimelcloudCmd)
          ->setName(__('Valeur température consigne', __FILE__))
          ->setLogicalId('SetTemperature_Value')
          ->setOrder(6)
          ->setIsVisible(0)
          ->setIsHistorized(1)
          ->setType('info')
          ->setSubType('numeric')
          ->setUnite('°C')
          ->setDisplay('generic_type', 'THERMOSTAT_SETPOINT')
          ->setEqLogic_id($this->getId());
          $SetTemperature_Value->save();
        }
        
        $SetTemperature = $this->getCmd(null, 'SetTemperature');
        if(!is_object($SetTemperature)) {
          $SetTemperature = (new mitsubishimelcloudCmd)
          ->setName(__('Température consigne', __FILE__))
          ->setLogicalId('SetTemperature')
          ->setOrder(7)
          ->setIsVisible(1)
          ->setIsHistorized(0)
          ->setType('action')
          ->setSubType('slider')
          ->setConfiguration('minValue', 10)
          ->setConfiguration('maxValue', 30)
          ->setConfiguration('step', 1)
          ->setUnite('°C')
          ->setTemplate('dashboard', 'TemperatureMitsubishi')
          ->setTemplate('mobile', 'TemperatureMitsubishi')
          ->setDisplay('generic_type', 'THERMOSTAT_SETPOINT')
          ->setConfiguration('updateCmdId', $SetTemperature_Value->getEqLogic_id())
          ->setValue($SetTemperature_Value->getId())
          ->setEqLogic_id($this->getId());
          $SetTemperature->save();
        }
        
        $OperationMode_Value = $this->getCmd(null, 'OperationMode_Value');
        if(!is_object($OperationMode_Value)) {
          $OperationMode_Value = (new mitsubishimelcloudCmd)
          ->setName(__('Mode actif', __FILE__))
          ->setLogicalId('OperationMode_Value')
          ->setOrder(8)
          ->setIsVisible(0)
          ->setIsHistorized(1)
          ->setType('info')
          ->setSubType('numeric')
          ->setDisplay('generic_type', 'THERMOSTAT_MODE')
          ->setEqLogic_id($this->getId());
          $OperationMode_Value->save();
        }
        
        $OperationMode = $this->getCmd(null, 'OperationMode');
        if(!is_object($OperationMode)) {
          $OperationMode = (new mitsubishimelcloudCmd)
          ->setName(__('Mode', __FILE__))
          ->setLogicalId('OperationMode')
          ->setOrder(9)
          ->setIsVisible(1)
          ->setIsHistorized(0)
          ->setType('action')
          ->setSubType('slider')
          ->setConfiguration('minValue', 1)
          ->setConfiguration('maxValue', 8)
          ->setTemplate('dashboard', 'ModeMitsubishi')
          ->setTemplate('mobile', 'ModeMitsubishi')
          ->setDisplay('generic_type', 'THERMOSTAT_SET_MODE')
          ->setConfiguration('updateCmdId', $OperationMode_Value->getEqLogic_id())
          ->setValue($OperationMode_Value->getId())
          ->setEqLogic_id($this->getId());
          $OperationMode->save();
        }
        
        $FanSpeed_Value = $this->getCmd(null, 'FanSpeed_Value');
        if(!is_object($FanSpeed_Value)) {
          $FanSpeed_Value = (new mitsubishimelcloudCmd)
          ->setName(__('Valeur vitesse ventilation', __FILE__))
          ->setLogicalId('FanSpeed_Value')
          ->setOrder(10)
          ->setIsVisible(0)
          ->setIsHistorized(1)
          ->setType('info')
          ->setSubType('numeric')
          ->setDisplay('generic_type', 'FAN_SPEED_STATE')
          ->setEqLogic_id($this->getId());
          $FanSpeed_Value->save();
        }
        
        $FanSpeed = $this->getCmd(null, 'FanSpeed');
        if(!is_object($FanSpeed)) {
          $FanSpeed = (new mitsubishimelcloudCmd)
          ->setName(__('Vitesse ventilation', __FILE__))
          ->setLogicalId('FanSpeed')
          ->setOrder(11)
          ->setIsVisible(1)
          ->setIsHistorized(0)
          ->setType('action')
          ->setSubType('slider')
          ->setConfiguration('minValue', 0)
          ->setConfiguration('maxValue', 5)
          ->setTemplate('dashboard', 'FanSpeedMitsubishi')
          ->setTemplate('mobile', 'FanSpeedMitsubishi')
          ->setDisplay('generic_type', 'FAN_SPEED')
          ->setConfiguration('updateCmdId', $FanSpeed_Value->getEqLogic_id())
          ->setValue($FanSpeed_Value->getId())
          ->setEqLogic_id($this->getId());
          $FanSpeed->save();
        }
        
        $VaneVerticalDirection_Value = $this->getCmd(null, 'VaneVerticalDirection_Value');
        if(!is_object($VaneVerticalDirection_Value)) {
          $VaneVerticalDirection_Value = (new mitsubishimelcloudCmd)
          ->setName(__('Valeur position ailettes verticales', __FILE__))
          ->setLogicalId('VaneVerticalDirection_Value')
          ->setOrder(12)
          ->setIsVisible(0)
          ->setIsHistorized(1)
          ->setType('info')
          ->setSubType('numeric')
          ->setDisplay('generic_type', 'ROTATION_STATE')
          ->setEqLogic_id($this->getId());
          $VaneVerticalDirection_Value->save();
        }
        
        $VaneVerticalDirection = $this->getCmd(null, 'VaneVerticalDirection');
        if(!is_object($VaneVerticalDirection)) {
          $VaneVerticalDirection = (new mitsubishimelcloudCmd)
          ->setName(__('Position ailettes verticales', __FILE__))
          ->setLogicalId('VaneVerticalDirection')
          ->setOrder(13)
          ->setIsVisible(1)
          ->setIsHistorized(0)
          ->setType('action')
          ->setSubType('slider')
          ->setConfiguration(
              'listValue', 
              '0|Auto;1|1;2|2;3|3;4|4;5|5;7|Basculer'
            )
          ->setDisplay(
              'slider_placeholder',
              'Auto : 0 1 : 1 2 : 2 3 : 3 4 : 4 5 : 5 Basculer : 7'
            )
          ->setTemplate('dashboard', 'VaneVerticalDirectionMitsubishi')
          ->setTemplate('mobile', 'VaneVerticalDirectionMitsubishi')
          ->setDisplay('generic_type', 'ROTATION')
          ->setConfiguration('updateCmdId', $VaneVerticalDirection_Value->getEqLogic_id())
          ->setValue($VaneVerticalDirection_Value->getId())
          ->setEqLogic_id($this->getId());
          $VaneVerticalDirection->save();
        }
        
        $VaneHorizontalDirection_Value = $this->getCmd(null, 'VaneHorizontalDirection_Value');
        if(!is_object($VaneHorizontalDirection_Value)) {
          $VaneHorizontalDirection_Value = (new mitsubishimelcloudCmd)
          ->setName(__('Valeur position ailettes horizontales', __FILE__))
          ->setLogicalId('VaneHorizontalDirection_Value')
          ->setOrder(14)
          ->setIsVisible(0)
          ->setIsHistorized(1)
          ->setType('info')
          ->setSubType('numeric')
          ->setDisplay('generic_type', 'ROTATION_STATE')
          ->setEqLogic_id($this->getId());
          $VaneHorizontalDirection_Value->save();
        }
        
        $VaneHorizontalDirection = $this->getCmd(null, 'VaneHorizontalDirection');
        if(!is_object($VaneHorizontalDirection)) {
          $VaneHorizontalDirection = (new mitsubishimelcloudCmd)
          ->setName(__('Position ailettes horizontal', __FILE__))
          ->setLogicalId('VaneHorizontalDirection')
          ->setOrder(15)
          ->setIsVisible(1)
          ->setIsHistorized(0)
          ->setType('action')
          ->setSubType('slider')
          ->setConfiguration(
              'listValue', 
              '0|Auto;1|1;2|2;3|3;4|4;5|5;12|Basculer'
            )
          ->setDisplay(
              'slider_placeholder',
              'Auto : 0 1 : 1 2 : 2 3 : 3 4 : 4 5 : 5 Basculer : 12'
            )
          ->setTemplate('dashboard', 'VaneVerticalDirectionMitsubishi')
          ->setTemplate('mobile', 'VaneVerticalDirectionMitsubishi')
          ->setDisplay('generic_type', 'ROTATION')
          ->setConfiguration('updateCmdId', $VaneHorizontalDirection_Value->getEqLogic_id())
          ->setValue($VaneHorizontalDirection_Value->getId())
          ->setEqLogic_id($this->getId());
          $VaneHorizontalDirection->save();
        }
      } elseif ($this->getConfiguration('typepac') == 'air/eau') {
        log::add(__CLASS__, 'error', __('Non supporté par le plugin pour le moment. Merci de contacter le développeur', __FILE__));
        return;
      } else {
        log::add(__CLASS__, 'error', __('Pas de type de PAC trouvé', __FILE__));
        return;
      }
    }
  }
}

class mitsubishimelcloudCmd extends cmd
{
  // Exécution d'une commande  
  public function execute($_options = array()) {
    if('refresh' == $this->logicalId) {
      mitsubishimelcloud::SynchronizeMELCloud();
    }
    if('OperationMode' == $this->logicalId) {
      log::add('mitsubishimelcloud', 'debug', 'New order requested, value : '.$_options['message']);
    }
  }
}