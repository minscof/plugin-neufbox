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

 $('#bt_cronGenerator').on('click',function(){
    jeedom.getCronSelectModal({},function (result) {
        $('.eqLogicAttr[data-l1key=configuration][data-l2key=autorefresh]').value(result.value);
    });
});

 $('.cmdAction[data-action=refresh]').on('click', function () {
	 bootbox.confirm('{{Etes-vous sûr de vouloir mettre à jour automatiquement la liste de toutes les commandes ? Les commandes existantes seront conservées.}}', function (result) {
	        if (result) {
	            $.ajax({
	                type: "POST", // méthode de transmission des données au fichier php
	                url: "plugins/neufbox/core/ajax/neufbox.ajax.php", 
	                data: {
	                    action: "refreshCmd",
	                    id: $('.eqLogicAttr[data-l1key=id]').value()
	                },
	                dataType: 'json',
	                global: false,
	                error: function (request, status, error) {
	                    handleAjaxError(request, status, error);
	                },
	                success: function (data) { 
	                    if (data.state != 'ok') {
	                        $('#div_alert').showAlert({message: data.result, level: 'danger'});
	                        return;
	                    }
	                    $('#div_alert').showAlert({message: '{{Opération réalisée avec succès}}', level: 'success'});
	                    $('.li_eqLogic[data-eqLogic_id=' + $('.eqLogicAttr[data-l1key=id]').value() + ']').click();
	                }
	            });
	        }
	    });
 });
 
 $('#bt_removeAll').on('click', function () {
	 console.log('init removeAll action');
	 bootbox.confirm('{{Etes-vous sûr de vouloir supprimer tous les équipements IP définis ?}}', function (result) {
	        if (result) {
	            $.ajax({
	                type: "POST", // méthode de transmission des données au fichier php
	                url: "plugins/neufbox/core/ajax/neufbox.ajax.php", 
	                data: {
	                    action: "removeAll",
	                    id: $('.eqLogicAttr[data-l1key=id]').value()
	                },
	                dataType: 'json',
	                global: false,
	                error: function (request, status, error) {
	                    handleAjaxError(request, status, error);
	                },
	                success: function (data) { 
	                    if (data.state != 'ok') {
	                        $('#div_alert').showAlert({message: data.result, level: 'danger'});
	                        return;
	                    }
	                    $('#div_alert').showAlert({message: '{{Opération réalisée avec succès}}', level: 'success'});
	                    //$('.li_eqLogic[data-eqLogic_id=' + $('.eqLogicAttr[data-l1key=id]').value() + ']').click();
	                }
	            });
	        }
	    });
	 console.log('end removeAll action');
 });
 
 $("#table_cmd").sortable({axis: "y", cursor: "move", items: ".cmd", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true});

function addCmdToTable(_cmd) {
    if (!isset(_cmd)) {
        var _cmd = {configuration: {}};
    }
    if (init(_cmd.logicalId) == 'refresh') {
       //return;
    }

      var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';
      tr += '<td>';
      tr += '<span class="cmdAttr" data-l1key="id" ></span>';
      tr += '</td>';
      tr += '<td>';
      tr += '<input class="cmdAttr form-control input-sm" data-l1key="type" value="info" style="display : none;">';
      tr += '<span class="cmdAttr form-control input-sm" data-l1key="name" ></span>';
      tr += '<span class="cmdAttr form-control input-sm" data-l1key="subType" style="display : none;">';
      tr += '</td>';

      tr += '<td>';
      tr += '<span><input type="checkbox" class="cmdAttr" data-l1key="isHistorized"  data-size="mini" data-label-text="{{Historiser}}" />{{Historiser}}</span> ';
      tr += '</td>';
      tr += '<td>';
      if (is_numeric(_cmd.id)) {
          tr += '<a class="btn btn-default btn-xs cmdAction expertModeVisible" data-action="configure"><i class="fa fa-cogs"></i></a> ';
          tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fa fa-rss"></i> {{Tester}}</a>';
      }
      tr += '</td>';
      tr += '</tr>';
      $('#table_info tbody').append(tr);
      $('#table_info tbody tr:last').setValues(_cmd, '.cmdAttr');
     
}
