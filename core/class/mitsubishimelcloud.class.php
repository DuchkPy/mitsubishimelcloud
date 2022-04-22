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
    curl_setopt($ch, CURLOPT_URL, "https://app.melcloud.com/Mitsubishi.Wifi.Client/Login/ClientLogin");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt(
      $ch,
      CURLOPT_POSTFIELDS,
      "Email=" . $Email . "&Password=" . $Password . "&Language=" . $Language . "&AppVersion=" . $AppVersion . "&Persist=true&CaptchaChallenge=null&CaptchaChallenge=null"
    );
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $server_output = curl_exec($ch);
    curl_close($ch);

    $json = json_decode($server_output, true);

    if ($json['ErrorId'] == null) {
      log::add(__CLASS__, 'debug', 'Login OK.');
      config::save("Token", $json['LoginData']['ContextKey'], 'mitsubishimelcloud');
      ajax::success();
    } elseif ($json['ErrorId'] == 1) {
      config::save("Token", __('Login ERROR : identifiant ou mot de passe MELCloud incorrect', __FILE__), 'mitsubishimelcloud');
      log::add(__CLASS__, 'debug', __('Login ERROR : identifiant ou mot de passe MELCloud incorrect.', __FILE__));
    } else {
      config::save("Token", 'Login ERROR : code n°' . $json['ErrorId'], 'mitsubishimelcloud');
      log::add(__CLASS__, 'debug', 'Login ERROR : code n°' . $json['ErrorId']);
    }
  }

  /** Collect data from MELCloud app */
  public static function SynchronizeMELCloud() {
    $Token = config::byKey('Token', __CLASS__);
    if ($Token == '' || substr($Token, 0, 11) == 'Login ERROR') {
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
  private static function SynchronizeCommands($device) {
    log::add(__CLASS__, 'debug', 'Synchronize : ' . $device['DeviceName']);
    if ($device['DeviceID'] == '') return;
    log::add(__CLASS__, 'debug', $device['DeviceID'] . ' ' . $device['DeviceName']);

    $theEQlogic = eqLogic::byTypeAndSearchConfiguration(__CLASS__, '"MachineName":"' . $device['DeviceName'] . '"');

    if (count($theEQlogic) == 0) {
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
      if ($mylogical->getIsEnable()) {
        log::add(__CLASS__, 'debug', 'Set device ' . $device['Device']['DeviceID']);
        $mylogical->setConfiguration('deviceid', $device['Device']['DeviceID']);
        $mylogical->setConfiguration('buildid', $device['BuildingID']);

        if ($device['Device']['DeviceType'] == '0') {
          log::add(__CLASS__, 'debug', __('PAC type air/air', __FILE__));
          $mylogical->setConfiguration('typepac', 'air/air');
        } elseif ($device['Device']['DeviceType'] == '1') {
          log::add(__CLASS__, 'debug', __('PAC type air/eau', __FILE__));
          $mylogical->setConfiguration('typepac', 'air/eau');
        } else {
          log::add(__CLASS__, 'error', __('Pas de type de PAC trouvé', __FILE__));
          return;
        }

        if ($mylogical->getConfiguration('rubriques') == '') {
            $mylogical->setConfiguration('rubriques', print_r($device['Device'], true));
        }

        $mylogical->save();

        foreach ($mylogical->getCmd() as $cmd) {
          switch ($cmd->getLogicalId()) {
            case 'On':
            case 'Off':
            case 'refresh':
            case 'Power':
              // ON ; OFF ; Refresh we don't take care about them
              log::add(__CLASS__, 'debug', 'log : '.$cmd->getLogicalId().__(' : On ne traite pas cette commande', __FILE__));
              break;

            case 'FanSpeedValue':
            case 'OperationModeValue':
            case 'SetTemperatureValue':
              // We do the same operation for these 3 "xxValue"
              $operation = str_replace("Value", "", $cmd->getLogicalId());
              log::add(__CLASS__, 'debug', 'log : '.$cmd->getLogicalId().__(' pour ', __FILE__).$operation.__(' et la valeur ', __FILE__).$device['Device'][$operation]);
              $cmd->setCollectDate('');
              $cmd->event($device['Device'][$operation]);
              $cmd->save();
              break;

            case 'FanSpeed':
              log::add(__CLASS__, 'debug', __('log pour le FanSpeed ', __FILE__).$cmd->getLogicalId().' '.$device['Device']['NumberOfFanSpeeds']);
              $cmd->setConfiguration('maxValue', $device['Device']['NumberOfFanSpeeds']);
              log::add(__CLASS__, 'debug', __('log pour le FanSpeed sur le auto : ', __FILE__).$device['Device']['HasAutomaticFanSpeed']);
              $arr = array ('hasAutomatic' => $device['Device']['HasAutomaticFanSpeed']);
              $cmd->setDisplay('parameters', $arr);
              $cmd->save();
              break;

            case 'SetTemperature':
              // Define Min / Max temperature for slider AND current requested temperature
              $stepArray = array('step' => floatval($device['Device']['TemperatureIncrement']));
              $cmd->setDisplay('parameters', $stepArray);
              if ($device['Device']['OperationMode'] == 1) {
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

            case 'LastTimeStamp':
              $cmd->event(str_replace('T', ' ', $device['Device'][$cmd->getLogicalId()]));
              $cmd->save();
              break;

            default:
              // For : Power, RoomTemperature, OperationMode
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


  //Fonction exécutée automatiquement toutes les 5 minutes par Jeedom
  public static function cron5() {  }
}

class mitsubishimelcloudCmd extends cmd
{
  // Exécution d'une commande  
  public function execute($_options = array())
  {
  }
}