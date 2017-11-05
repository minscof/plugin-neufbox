<?php
if (! isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}
sendVarToJS('eqType', 'neufbox');
$eqLogics = eqLogic::byType('neufbox');
if (empty($eqLogics)) {
    $eqLogic = new neufbox();
    
    $eqLogic->setLogicalId(config::byKey('macBox', 'neufbox'));
    $eqLogic->setName(config::byKey('nameBox', 'neufbox'));
    $eqLogic->setEqType_name('neufbox');
    $eqLogic->setConfiguration('name', config::byKey('nameBox', 'neufbox'));
    $eqLogic->setConfiguration('ip', config::byKey('ipBox', 'neufbox'));
    $eqLogic->setConfiguration('mac', config::byKey('macBox', 'neufbox'));
    $eqLogic->setConfiguration('type', 'box');
    $eqLogic->setIsVisible(1);
    $eqLogic->setIsEnable(1);
    $eqLogic->save();
    $eqLogics[] = $eqLogic;
}
?>
<div class="row row-overflow">
	<div class="col-lg-2 col-md-3 col-sm-4">
		<div class="bs-sidebar">
			<ul id="ul_eqLogic" class="nav nav-list bs-sidenav">
				<a class="btn btn-default eqLogicAction"
					style="width: 100%; margin-top: 5px; margin-bottom: 5px;"
					data-action="add"><i class="fa fa-plus-circle"></i> {{Ajouter un
					équipement}}</a>
				<li class="filter" style="margin-bottom: 5px;"><input
					class="filter form-control input-sm" placeholder="{{Rechercher}}"
					style="width: 100%" /></li>
                <?php
                foreach ($eqLogics as $eqLogic) {
                    $opacity = ($eqLogic->getIsEnable()) ? '' : jeedom::getConfiguration('eqLogic:style:noactive');
                    echo '<li class="cursor li_eqLogic" data-eqLogic_id="' . $eqLogic->getId() . '"  style="' . $opacity . '"><a>' . $eqLogic->getHumanName(true) . '</a></li>';
                }
                ?>
            </ul>
		</div>
	</div>

	<div class="col-lg-10 col-md-9 col-sm-8 eqLogicThumbnailDisplay"
		style="border-left: solid 1px #EEE; padding-left: 25px;">
		<legend>
			<i class="fa fa-cog"></i> {{Gestion}}
		</legend>
		<div class="eqLogicThumbnailContainer">
			<div class="cursor eqLogicAction" data-action="add"
				style="background-color: #ffffff; height: 200px; margin-bottom: 10px; padding: 5px; border-radius: 2px; width: 160px; margin-left: 10px;">
				<center>
					<i class="fa fa-plus-circle"
						style="font-size: 7em; color: #94ca02;"></i>
				</center>
				<span
					style="font-size: 1.1em; position: relative; top: 23px; word-break: break-all; white-space: pre-wrap; word-wrap: break-word; color: #94ca02"><center>{{Ajouter}}</center></span>
			</div>

			<div class="cursor eqLogicAction" data-action="removeAll"
				id="bt_removeAll"
				style="background-color: #ffffff; height: 200px; margin-bottom: 10px; padding: 5px; border-radius: 2px; width: 160px; margin-left: 10px;">
				<center>
					<i class="fa fa-minus-circle"
						style="font-size: 7em; color: #FA5858;"></i>
				</center>
				<span
					style="font-size: 1.1em; position: relative; top: 23px; word-break: break-all; white-space: pre-wrap; word-wrap: break-word; color: #FA5858"><center>{{Supprimer tous}}</center></span>
			</div>

			<div class="cursor eqLogicAction" data-action="gotoPluginConf"
				style="background-color: #ffffff; height: 120px; margin-bottom: 10px; padding: 5px; border-radius: 2px; width: 160px; margin-left: 10px;">
				<center>
					<i class="fa fa-wrench" style="font-size: 6em; color: #767676;"></i>
				</center>
				<span
					style="font-size: 1.1em; position: relative; top: 15px; word-break: break-all; white-space: pre-wrap; word-wrap: break-word; color: #767676"><center>{{Configuration}}</center></span>
			</div>
			<div class="cursor" id="bt_healthneufbox"
				style="background-color: #ffffff; height: 120px; margin-bottom: 10px; padding: 5px; border-radius: 2px; width: 160px; margin-left: 10px;">
				<center>
					<i class="fa fa-medkit" style="font-size: 6em; color: #767676;"></i>
				</center>
				<span
					style="font-size: 1.1em; position: relative; top: 15px; word-break: break-all; white-space: pre-wrap; word-wrap: break-word; color: #767676"><center>{{Santé}}</center></span>
			</div>
		</div>

		<legend>
			<i class="fa fa-eye fa-2"></i> {{Mes équipements IP }}
		</legend>
		<div class="eqLogicThumbnailContainer">
	        <?php
        foreach ($eqLogics as $eqLogic) {
            $opacity = ($eqLogic->getIsEnable()) ? '' : jeedom::getConfiguration('eqLogic:style:noactive');
            echo '<div class="eqLogicDisplayCard cursor" data-eqLogic_id="' . $eqLogic->getId() . '" style="background-color : #ffffff; height : 200px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;' . $opacity . '" >';
            echo "<center>";
            echo '<img src="plugins/neufbox/doc/images/neufbox_icon.png" height="105" width="95" />';
            echo "</center>";
            echo '<span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;"><center>' . $eqLogic->getHumanName(true, true) . '</center></span>';
            echo '</div>';
        }
        ?>
        </div>
	</div>

	<div class="col-lg-10 col-md-9 col-sm-8 eqLogic"
		style="border-left: solid 1px #EEE; padding-left: 25px; display: none;">
		<ul class="nav nav-tabs" role="tablist">
			<li role="presentation" class="active"><a href="#eqlogictab"
				aria-controls="home" role="tab" data-toggle="tab"><i
					class="fa fa-eye"></i> {{Equipement}}</a></li>
			<li role="presentation"><a href="#commandtab" aria-controls="profile"
				role="tab" data-toggle="tab"><i class="fa fa-list-alt"></i>
					{{Commandes}}</a></li>
		</ul>
		<div class="tab-content"
			style="height: calc(100% - 50px); overflow: auto; overflow-x: hidden;">
			<div role="tabpanel" class="tab-pane active" id="eqlogictab">
				<form class="form-horizontal">
					<fieldset>
						<legend>
							<i class="fa fa-arrow-circle-left eqLogicAction cursor"
								data-action="returnToThumbnailDisplay"></i> {{Général}}<i
								class='fa fa-cogs eqLogicAction pull-right cursor expertModeVisible'
								data-action='configure'></i>
						</legend>
						<div class="form-group">
							<label class="col-sm-3 control-label">{{Nom de l'équipement}}</label>
							<div class="col-sm-3">
								<input type="text" class="eqLogicAttr form-control"
									data-l1key="id" style="display: none;" /> <input type="text"
									class="eqLogicAttr form-control" data-l1key="name"
									placeholder="{{Nom de l'équipement}}" />
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-3 control-label">{{Objet parent}}</label>
							<div class="col-sm-3">
								<select id="sel_object" class="eqLogicAttr form-control"
									data-l1key="object_id">
									<option value="">{{Aucun}}</option>
                                <?php
                                foreach (object::all() as $object) {
                                    echo '<option value="' . $object->getId() . '">' . $object->getName() . '</option>';
                                }
                                ?>
                        		</select>
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-3 control-label">{{Catégorie}}</label>
							<div class="col-sm-6">
                            <?php
                            foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
                                echo '<label class="checkbox-inline">';
                                echo '<input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '" />' . $value['name'];
                                echo '</label>';
                            }
                            ?>
                    		</div>
						</div>
						<div class="form-group">
							<label class="col-sm-3 control-label"></label>
							<div class="col-sm-9">
								<label class="checkbox-inline"><input type="checkbox"
									class="eqLogicAttr" data-l1key="isEnable" checked />{{Activer}}</label>
								<label class="checkbox-inline"><input type="checkbox"
									class="eqLogicAttr" data-l1key="isVisible" checked />{{Visible}}</label>
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-3 control-label">{{Auto-actualisation
								(cron)}}</label>
							<div class="col-sm-3">
								<input type="text" class="eqLogicAttr form-control"
									data-l1key="configuration" data-l2key="autorefresh" />
							</div>
							<div class="col-sm-1">
								<i class="fa fa-question-circle cursor floatright"
									id="bt_cronGenerator"></i>
							</div>

						</div>

						<div class="form-group">
							<label class="col-sm-3 control-label">{{Dashboard}}</label>
							<div class="col-sm-9">
								<label class="checkbox-inline"><input type="checkbox"
									class="eqLogicAttr" data-l1key="configuration"
									data-l2key="displayMac" />{{MAC}}</label> <label
									class="checkbox-inline"><input type="checkbox"
									class="eqLogicAttr" data-l1key="configuration"
									data-l2key="displayIp" checked />{{IP}}</label> <label
									class="checkbox-inline"><input type="checkbox"
									class="eqLogicAttr" data-l1key="configuration"
									data-l2key="displayHostname" />{{Hostname}}</label> <label
									class="checkbox-inline"><input type="checkbox"
									class="eqLogicAttr" data-l1key="configuration"
									data-l2key="displayTimer" checked />{{Keepalive timer}}</label>
							</div>
						</div>
						<div class="alert alert-info">
							/!\ Important: Si le paramètre cron n'est pas renseigné, le
							plugin ne se rafraichira que manuellement en cliquant sur les
							flèches en haut à droite du dashboard<br> - Exemple actualisation
							cron toutes les 5 minutes: */5 * * * * ou toutes les minutes: */1
							* * * *<br /> - La valeur Keepalive correspond au délai suivant
							la dernière activité d'un host au bout duquel il n'est plus
							considéré comme actif
						</div>
					</fieldset>
				</form>
			</div>
			
			<div role="tabpanel" class="tab-pane" id="commandtab">
				<table id="table_info" class="table table-bordered table-condensed">
					<thead>
						<tr>
							<th style="width: 50px;">{{ID}}</th>
							<th style="width: 200px;">{{Nom}}</th>
							<th style="width: 200px;">{{Paramètres}}</th>
							<th style="width: 150px;"></th>
						</tr>
					</thead>
					<tbody>
					</tbody>
				</table>
			</div>
			<form class="form-horizontal">
				<fieldset>
					<div class="form-actions">
						<a class="btn btn-danger eqLogicAction" data-action="remove"><i
							class="fa fa-minus-circle"></i> {{Supprimer}}</a> <a
							class="btn btn-success eqLogicAction" data-action="save"><i
							class="fa fa-check-circle"></i> {{Sauvegarder}}</a>
					</div>
				</fieldset>
			</form>
		</div>
	</div>
</div>

<?php include_file('desktop', 'neufbox', 'js', 'neufbox');?>
<?php include_file('core', 'plugin.template', 'js');?>
