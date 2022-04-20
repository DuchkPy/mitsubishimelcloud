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
include_file('core', 'authentification', 'php');
if (!isConnect()) {
  include_file('desktop', '404', 'php');
  die();
}
?>
<form class="form-horizontal" name="config_form">
  <fieldset>
    <legend><i class="fas fa-cogs"></i>{{Général :}}</legend>
    <div class="form-group">
      <label class="col-md-4 control-label">{{Email MELCloud :}}
        <sup><i class="fas fa-question-circle tooltips" title="{{Identifiant mail pour se connecter à MELCloud}}"></i></sup>
      </label>
      <div class="col-md-4">
        <input class="configKey form-control" data-l1key="Email" id="email" onchange="ConfigUpdate()" />
      </div>
    </div>
    <div class="form-group">
      <label class="col-md-4 control-label">{{Mot de passe MELCloud :}}
        <sup><i class="fas fa-question-circle tooltips" title="{{Mot de passe pour se connecter à MELCloud}}"></i></sup>
      </label>
      <div class="col-md-4">
        <input class="configKey form-control" data-l1key="Password" id="password" onchange="ConfigUpdate()" />
      </div>
    </div>
    <div class="form-group">
      <label class="col-md-4 control-label">{{Langue :}}
        <sup><i class="fas fa-question-circle tooltips"  title="{{Langue utilisée par l'application}}"></i></sup>
      </label>
      <div class="col-md-4">
        <select class="configKey form-control" data-l1key="Language" onchange="ConfigUpdate()">
          <option value="0" <?php if(config::byKey('Language', 'mitsubishimelcloud') == "0") echo 'selected="selected"'; ?> >English</option>
          <option value="1" <?php if(config::byKey('Language', 'mitsubishimelcloud') == "1") echo 'selected="selected"'; ?> >Български</option>
          <option value="2" <?php if(config::byKey('Language', 'mitsubishimelcloud') == "2") echo 'selected="selected"'; ?> >Čeština</option>
          <option value="3" <?php if(config::byKey('Language', 'mitsubishimelcloud') == "3") echo 'selected="selected"'; ?> >Dansk</option>
          <option value="4" <?php if(config::byKey('Language', 'mitsubishimelcloud') == "4") echo 'selected="selected"'; ?> >Deutsch</option>
          <option value="5" <?php if(config::byKey('Language', 'mitsubishimelcloud') == "5") echo 'selected="selected"'; ?> >Eesti</option>
          <option value="6" <?php if(config::byKey('Language', 'mitsubishimelcloud') == "6") echo 'selected="selected"'; ?> >Español</option>
          <option value="7" <?php if(config::byKey('Language', 'mitsubishimelcloud') == "7") echo 'selected="selected"'; ?> >Français</option>
          <option value="8" <?php if(config::byKey('Language', 'mitsubishimelcloud') == "8") echo 'selected="selected"'; ?> >Հայերեն</option>
          <option value="9" <?php if(config::byKey('Language', 'mitsubishimelcloud') == "9") echo 'selected="selected"'; ?> >Latviešu</option>
          <option value="10" <?php if(config::byKey('Language', 'mitsubishimelcloud') == "10") echo 'selected="selected"'; ?> >Lietuvių</option>
          <option value="11" <?php if(config::byKey('Language', 'mitsubishimelcloud') == "11") echo 'selected="selected"'; ?> >Magyar</option>
          <option value="12" <?php if(config::byKey('Language', 'mitsubishimelcloud') == "12") echo 'selected="selected"'; ?> >Nederlands</option>
          <option value="13" <?php if(config::byKey('Language', 'mitsubishimelcloud') == "13") echo 'selected="selected"'; ?> >Norsk</option>
          <option value="14" <?php if(config::byKey('Language', 'mitsubishimelcloud') == "14") echo 'selected="selected"'; ?> >Polski</option>
          <option value="15" <?php if(config::byKey('Language', 'mitsubishimelcloud') == "15") echo 'selected="selected"'; ?> >Português</option>
          <option value="16" <?php if(config::byKey('Language', 'mitsubishimelcloud') == "16") echo 'selected="selected"'; ?> >Русский</option>
          <option value="17" <?php if(config::byKey('Language', 'mitsubishimelcloud') == "17") echo 'selected="selected"'; ?> >Suomi</option>
          <option value="18" <?php if(config::byKey('Language', 'mitsubishimelcloud') == "18") echo 'selected="selected"'; ?> >Svenska</option>
          <option value="19" <?php if(config::byKey('Language', 'mitsubishimelcloud') == "19") echo 'selected="selected"'; ?> >Italiano</option>
          <option value="20" <?php if(config::byKey('Language', 'mitsubishimelcloud') == "20") echo 'selected="selected"'; ?> >Українська</option>
          <option value="21" <?php if(config::byKey('Language', 'mitsubishimelcloud') == "21") echo 'selected="selected"'; ?> >Türkçe</option>
          <option value="22" <?php if(config::byKey('Language', 'mitsubishimelcloud') == "22") echo 'selected="selected"'; ?> >Ελληνικά</option>
          <option value="23" <?php if(config::byKey('Language', 'mitsubishimelcloud') == "23") echo 'selected="selected"'; ?> >Hrvatski - Srpski</option>
          <option value="24" <?php if(config::byKey('Language', 'mitsubishimelcloud') == "24") echo 'selected="selected"'; ?> >Română</option>
          <option value="25" <?php if(config::byKey('Language', 'mitsubishimelcloud') == "25") echo 'selected="selected"'; ?> >Slovenščina</option>
          <option value="26" <?php if(config::byKey('Language', 'mitsubishimelcloud') == "26") echo 'selected="selected"'; ?> >Shqip</option>
        </select>
      </div>
    </div>
    <br />
    <div class="form-group">
      <label class="col-md-4 control-label">{{Obtenir token :}} </label>
      <div class="col-md-4">
        <a id="bt_GetToken" class="form-control btn btn-success bt_GetToken">
          <i class="fas fa-download"></i>
          {{Récupérer le Token MELCloud}}
        </a>
      </div>
    </div>
    <div class="form-group">
      <label class="col-md-4 control-label">{{Token :}} </label>
      <div class="col-md-4">
        <input class="configKey form-control" data-l1key="Token" id="Token" readonly />
      </div>
    </div>
    <div class="form-group">
      <label class="col-md-4 control-label">{{Synchroniser équipements :}} </label>
      <div class="col-md-4">
        <a id="bt_Synch" class="form-control btn btn-success bt_Synch">
          <i class="fas fa-sync"></i>
          {{Récupérer les équipements MELCloud}}
        </a>
      </div>
    </div>
  </fieldset>
  <fieldset>
    <legend><i class="fas fa-exclamation-triangle"></i>{{Avancé :}}</legend>
    <div class="form-group">
      <label class="col-md-4 control-label">{{Application version :}}
        <sup><i class="fas fa-question-circle tooltips"  title="{{Version de l'application. Trouvable dans le code source de la page https://app.melcloud.com/}}"></i></sup>
      </label>
      <div class="col-md-4">
        <input class="configKey form-control" data-l1key="AppVersion" onchange="ConfigUpdate()" />
      </div>
    </div>
  </fieldset>
</form>

<script>
  /** Collect token from MELCloud app */
  $('.bt_GetToken').on('click', function () {
    //Check if form is completly filled and saved
    if(document.getElementById('email').value == '' || document.getElementById('password').value == '') {
      $('#div_alert').showAlert({message: '{{Merci de remplir l\'email et le mot de passe avant de récupérer le token}}', level: 'danger'});
    } else {
      if(ComptChamp > 3) {
        $('#div_alert').showAlert({message: '{{Merci de sauvegarder la configuration avant de récupérer le token}}', level: 'danger'});
      } else {
        $('#div_alert').showAlert({message: '{{En cours de récupération du token...}}', level: 'warning'});
        $.ajax({
          type: 'POST',
          url: 'plugins/mitsubishimelcloud/core/ajax/mitsubishimelcloud.ajax.php',
          data: {
            action: 'GetToken',
            id : $(this).closest('.slaveConfig').attr('data-slave_id')
          },
          dataType: 'json',
          error: function (request, status, error) {
            handleAjaxError(request, status, error, $('#div_alert'));
            $('#div_alert').showAlert({message: '{{Token non-récupéré}}', level: 'error'});
          },
          success: function (data) {
            $('#div_alert').showAlert({message: '{{Token récupéré}}', level: 'success'});
            location.reload();
          }
        });
      }
    }
  });

  /** Collect equipment from MELCloud app */
  $('.bt_Synch').on('click', function () {
    var Token = document.getElementById('Token').value;
    if(Token.substring(0, 11) == 'Login ERROR' || Token.length == 0) {
      alert('{{Merci de récupérer le token en premier}}');
    } else {
      $.ajax({
        type: 'POST',
        url: 'plugins/mitsubishimelcloud/core/ajax/mitsubishimelcloud.ajax.php',
        data: {
          action: 'SynchronizeMELCloud',
          id : $(this).closest('.slaveConfig').attr('data-slave_id')
        },
        dataType: 'json',
        error: function (request, status, error) {
          handleAjaxError(request, status, error, $('#div_alert'));
          $('#div_alert').showAlert({message: '{{Erreur de synchronisation.}}', level: 'error'});
        },
        success: function (data) {
          $('#div_alert').showAlert({message: '{{Synchronisation réussie.}}', level: 'success'});
        }
      });
    }
    
  });

  //Some tricks to ensure the user have saved before trying to collect the token
  var ComptChamp = 0;
  function ConfigUpdate() {
    ComptChamp++;
  }
  document.getElementById("bt_savePluginConfig").addEventListener("click", function() {
    ComptChamp = 3;
  });
</script>
<!-- Inclusion du fichier javascript du plugin (dossier, nom_du_fichier, extension_du_fichier, id_du_plugin) -->
<?php include_file('desktop', 'mitsubishimelcloud', 'js', 'mitsubishimelcloud');?>