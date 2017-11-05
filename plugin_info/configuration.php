<?php

/*
 * This file is part of Jeedom.
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
if (! isConnect()) {
    include_file('desktop', '404', 'php');
    die();
}
?>


<form class="form-horizontal">
	<fieldset>
		<div class="form-group">
			<label class="col-lg-2 control-label">{{Adresse IP de la neufbox}}</label>
			<div class="col-lg-2">
				<input type="text" class="configKey form-control" id="ipbox"
					data-l1key="ipBox" placeholder="192.168.0.1" />
			</div>
		</div>
	</fieldset>
	<div class="form-group">
		<label class="col-lg-2 control-label">{{admin login}}</label>
		<div class="col-lg-2">
			<input class="configKey form-control" data-l1key="neufboxLogin"
				value="admin" placeholder="{{login}}" />
		</div>
	</div>
	<div class="form-group">
		<label class="col-lg-2 control-label">{{mot de passe}}</label>
		<div class="col-lg-2">
			<input class="configKey form-control" data-l1key="neufboxPassword" type="password"
				value="xxxx" placeholder="{{mot de passe}}" />
		</div>
	</div>
	<fieldset>
		<div class="form-group">
			<label class="col-lg-2 control-label">{{Nombre d'équipements lors du
				dernier scan: }}</label>
			<div class="col-lg-2">
				<input id="neufbox_equipment_count" class="configKey form-control"
					data-l1key="IPdeviceCount" placeholder="" readonly />
			</div>
		</div>
	</fieldset>
	<div class="form-group" style="margin-top: -5px">
		<label class="col-lg-2 control-label">{{Equipments}}</label>
		<div class="col-lg-2">
			<a class="btn btn-warning" id="bt_scan"><i class="fa fa-check"></i>
				{{Scanner}}</a>
		</div>
	</div>
</form>
<script>
	$('#bt_scan').on('click', function () {
        bootbox.confirm('{{Voulez-vous lancer une auto découverte de vos équipements ? }}', function (result) {
			if (result) {
		        $.ajax({// fonction permettant de faire de l'ajax
		            type: "POST", // methode de transmission des données au fichier php
		            url: "plugins/neufbox/core/ajax/neufbox.ajax.php", // url du fichier php
		            data: {
		            	action: "scanIPdevices",
		            },
		            dataType: 'json',
		            error: function (request, status, error) {
		            	handleAjaxError(request, status, error);
		            },
		            success: function (data) { // si l'appel a bien fonctionné
			            if (data.state != 'ok') {
			            	$('#div_alert').showAlert({message: data.result, level: 'danger'});
			            	return;
			            }
			            $('#div_alert').showAlert({message: '{{Scan réussi}}', level: 'success'});
						$('#ul_plugin .li_plugin[data-plugin_id=neufbox]').click();
		        	}
    			});
    		}
    	});
    });
	$('#ipbox').on('blur', function () {
		console.log('change ipbox blur !');
                $.ajax({// fonction permettant de faire de l'ajax
		            type: "POST", // methode de transmission des données au fichier php
		            url: "plugins/neufbox/core/ajax/neufbox.ajax.php", // url du fichier php
		            data: {
		            	action: "getMacAddress",
		            	ipbox: $('#ipbox').val()
		            },
		            dataType: 'json',
		            error: function (request, status, error) {
		            	handleAjaxError(request, status, error);
		            },
		            success: function (data) { // si l'appel a bien fonctionné
			            if (data.state != 'ok') {
			            	$('#div_alert').showAlert({message: data.result, level: 'danger'});
			            	return;
			            }
			            //var toto=JSON.parse(data.result);
			            console.log('result='+data.result);
			            //$('#div_alert').showAlert({message: '{{Mac address =}}', level: 'success'});
						//$('#ul_plugin .li_plugin[data-plugin_id=neufbox]').click();
		        	}
    			});
    		
    	
    });
</script>