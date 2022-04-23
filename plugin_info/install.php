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

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';

/** Fonction exécutée automatiquement après l'installation du plugin */
  function mitsubishimelcloud_install() {
    $cron = cron::byClassAndFunction('mitsubishimelcloud', 'SynchronizeMELCloud');
    if (!is_object($cron)) {
      $cron = new cron();
      $cron->setClass('mitsubishimelcloud');
      $cron->setFunction('SynchronizeMELCloud');
      $cron->setEnable(1);
      $cron->setDeamon(0);
      $cron->setSchedule('*/5 * * * *');
      $cron->setTimeout('60');
      $cron->save();
    }

    //Add some configuration, language as French, and latest MELCloud version (when plugin was created)
    $Language = config::byKey('Language', 'mitsubishimelcloud');
    if (empty($Language)) {
      config::save('Language', '7', 'mitsubishimelcloud');
    }
    $AppVersion = config::byKey('AppVersion', 'mitsubishimelcloud');
    if (empty($AppVersion)) {
      config::save('AppVersion', '1.24.3.0', 'mitsubishimelcloud');
    }
  }

/** Fonction exécutée automatiquement après la mise à jour du plugin */
  function mitsubishimelcloud_update() {
    $cron = cron::byClassAndFunction('mitsubishimelcloud', 'SynchronizeMELCloud');
    if (!is_object($cron)) {
      $cron = new cron();
      $cron->setClass('mitsubishimelcloud');
      $cron->setFunction('SynchronizeMELCloud');
      $cron->setEnable(1);
      $cron->setDeamon(0);
      $cron->setSchedule('*/5 * * * *');
      $cron->setTimeout('60');
      $cron->save();
    }
    $cron->stop();
  }

/** Fonction exécutée automatiquement après la suppression du plugin */
  function mitsubishimelcloud_remove() {
    $cron = cron::byClassAndFunction('mitsubishimelcloud', 'SynchronizeMELCloud');
    if (is_object($cron)) {
      $cron->remove();
    }
  }

?>