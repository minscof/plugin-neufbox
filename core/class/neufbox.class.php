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

/* * ***************************Includes********************************* */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

class neufbox extends eqLogic
{

    /* * *************************Attributs****************************** */
    public static $_widgetPossibility = array(
        'custom' => true
    );

    /* * ***********************Methode static*************************** */
    public static function cron5()
    {
        log::add('neufbox', 'debug', __('Cron neufbox start ', __FILE__));
        try {
            $eqLogics = eqLogic::byType('neufbox');
            foreach ($eqLogics as $eqLogic) {
                $autorefresh = $eqLogic->getConfiguration('autorefresh');
                try {
                    $c = new Cron\CronExpression($autorefresh, new Cron\FieldFactory());
                    if ($c->isDue()) {
                        if ($eqLogic->getConfiguration('ip') !== config::byKey('ipBox', 'neufbox')) {
                            continue;
                        }
                        $eqLogic->refresh();
                    }
                } catch (Exception $exc) {
                    if ($eqLogic->getConfiguration('ip') == config::byKey('ipBox', 'neufbox')) {
                        log::add('neufbox', 'error', __('Expression cron non valide pour ', __FILE__) . $eqLogic->getHumanName() . ' : ' . $autorefresh);
                    }
                }
            }
        } catch (Exception $exc) {
            log::add('neufbox', 'error', __('Erreur pour ', __FILE__) . $autorefresh . ' : ' . $exc->getMessage());
        }
        log::add('neufbox', 'debug', __('Cron neufbox end ', __FILE__));
    }

    public static function dependancy_info()
    {
        $return = array();
        $return['log'] = 'neufbox_update';
        $return['progress_file'] = '/tmp/dependancy_neufbox_in_progress';
        if (exec('which python3 | wc -l') != 0 && substr(exec(dirname(__FILE__) . '/../../ressources/apineufbox.py ok'), 0, 2) == 'ok') {
            $return['state'] = 'ok';
        } else {
            $return['state'] = 'nok';
        }
        return $return;
    }

    public static function dependancy_install()
    {
        log::remove('neufbox_update');
        $cmd = 'sudo /bin/bash ' . dirname(__FILE__) . '/../../ressources/install.sh';
        $cmd .= ' >> ' . log::getPathToLog('neufbox_update') . ' 2>&1 &';
        exec($cmd);
    }

    public static function start()
    {
        self::cron5();
    }

    public static function removeAll()
    {
        $eqLogics = eqLogic::byType('neufbox');
        foreach ($eqLogics as $eqLogic) {
            if ($eqLogic->getConfiguration('ip') == config::byKey('ipBox', 'neufbox')) {
                continue;
            }
            $eqLogic->remove();
        }
        return array(
            true,
            'remove ok'
        );
    }
    
    public static function queryPhpBox() {
        log::add('neufbox','debug','* start queryPhpBox ***');
        $ip = config::byKey('ipBox', 'neufbox');
        $request = 'http://' . $ip . '/api/1.0/?method=lan.getHostsList';
        $request = new com_http($request);
        $xmlstr = $request->exec(5, 1);
        log::add('neufbox', 'debug', 'lan.getHostsList = ' . $xmlstr);
        $rsp = new SimpleXMLElement($xmlstr);
        $hosts=array();
        foreach ($rsp->children() as $host) {
            //log::add('neufbox','debug','host mac = '.$host['mac']);
            $hosts[(string) $host['mac']]=array('offline'=>((string) $host['status']!=='online'?(int) $host['alive']:''),'online'=>((string) $host['status']=='online'?(int) $host['alive']:''),'timer'=>((string) $host['status']=='online'?(int) $host['probe']:''),'isLock'=>false,'active'=> ((string) $host['status']=='online'?true:false),'ip'=>(string) $host['ip'],'iface'=>(string) $host['iface'],'name'=>(string) $host['name'],'keepalive'=>900,'status'=>(string) $host['status']);
        }
		return json_encode($hosts);
    }
    
    public static function queryWanStatusBox() {
        log::add('neufbox','debug','* start queryWanStatusBox ***');
        $ip = config::byKey('ipBox', 'neufbox');
        $request = 'http://' . $ip . '/api/1.0/?method=wan.getInfo';
        $request = new com_http($request);
        $xmlstr = $request->exec(5, 1);
        log::add('neufbox', 'debug', 'wan.getInfo = ' . $xmlstr);
        $rsp = new SimpleXMLElement($xmlstr);
        
        /*
        <?xml version="1.0" encoding="UTF-8"?>
<rsp stat="ok" version="1.0">
    <wan status="up" uptime="432792" ip_addr="77.133.215.232" infra="ftth" mode="ftth/routed" infra6="" status6="down" uptime6="" ipv6_addr="" />
</rsp>
*/
        foreach ($rsp->children() as $wan) {
            $status = $wan['status'];
        }
        return $status;
    }
    
    private static function queryBox() {
        $result2 = neufbox::queryPhpBox();
        return json_decode($result2);
        /*
        $ip = config::byKey('ipBox', 'neufbox');
        $json = shell_exec(__DIR__ . '/../../ressources/apineufbox.py update ' . $ip); // Execute le script python et récupère le json
        $a= print_r($json,true);
        log::add('neufbox', 'debug', '+++ dump3 = ' . $a);
        return json_decode($json);
        */
    }

    public static function refreshIPdevices()
    {
        $parsed_json = neufbox::queryBox();
        $a = print_r($parsed_json, true);
        log::add('neufbox', 'debug', '******** Début du scan des equipements IP ********:' . $a);
        if (! empty($parsed_json)) {
            foreach ($parsed_json as $mac => $device) {
                // $parsed_json->{$mac}->{$cmd->getConfiguration('info')}
                $a = print_r($device, true);
                log::add('neufbox', 'debug', 'mac=' . $mac . ' - device=' . $a);
                $eqLogic = neufbox::byLogicalId($mac, 'neufbox');
                if (! is_object($eqLogic)) {
                    $eqLogic = new neufbox();
                    $eqLogic->setLogicalId($mac);
                    $eqLogic->setIsVisible(1);
                }
                
                $eqLogic->setName(__(($device->{'name'} == '' ? $mac : $device->{'name'}), __FILE__));
                $eqLogic->setType('action');
                $eqLogic->setSubType('other');
                $eqLogic->setEqLogic_id($eqLogic->getId());
                $eqLogic->setConfiguration('mac', $mac);
                $eqLogic->setConfiguration('keepalive', $device->{'keepalive'});
                // $cmd->setConfiguration('info', $key);
                $eqLogic->setConfiguration('name', ($device->{'name'} == '' ? $mac : $device->{'name'}));
                log::add('neufbox', 'debug', 'before save mac=' . $mac . ' - device=' . $a);
                $eqLogic->save();
                log::add('neufbox', 'debug', 'after save mac=' . $mac . ' - device=' . $a);
            }
        }
        return;
    }

    public static function detection()
    {
        log::add('neufbox', 'debug', '******** Début du scan des equipements IP ********');
        // $result = neufbox::executeAction('scan');
        // if (!$result[0]) return $result;
        /*$ip = config::byKey('ipBox', 'neufbox');
        $json = shell_exec(__DIR__ . '/../../ressources/apineufbox.py update ' . $ip); // Execute le script python et récupère le json
        $parsed_json = json_decode($json);
        */
        $parsed_json = neufbox::queryBox();
        // $a=print_r($parsed_json,true);
        // log::add('neufbox','debug','******** equipements IP ******** '.$a);
        $count = 0;
        if (! empty($parsed_json)) {
            foreach ($parsed_json as $mac => $device) {
                $a = print_r($device, true);
                // log::add('neufbox','debug','mac='.$mac.' - device='.$a);
                $count ++;
                $a = '';
                $logicalName = ($device->{'name'} == '' ? $mac : $device->{'name'});
                $ip = '';
                // log::add('neufbox','debug','Equipement trouvé : '.$a);
                self::saveEquipment($logicalName, $mac, $ip);
                // if ($count > 10) break;
            }
        }
        
        config::save('IPdeviceCount', $count, 'neufbox');
        log::add('neufbox', 'info', '******** scan des équipements IP - nombre d\'équipements trouvés = ' . $count . ' ********');
        return array(
            true,
            '******** scan des équipements IP - nombre d\'équipements trouvés = ' . $count . ' ********'
        );
    }

    public static function saveEquipment($logicalName, $mac, $ip)
    {
        if (empty($logicalName)) {
            $logicalName = $mac;
        }
        
        // log::add('neufbox','debug','Début saveEquipment ='.$logicalName);
        $eqLogic = self::byLogicalId($mac, 'neufbox');
        if (is_object($eqLogic)) {
            $changed = false;
            log::add('neufbox', 'debug', 'Equipement déjà existant - mise à jour des informations de l\'équipement détecté : ' . $logicalName);
            if ($eqLogic->getConfiguration('ip') != $ip) {
                log::add('neufbox', 'info', 'Mise à jour de l\'adresse IP (' . $eqLogic->getConfiguration('ip') . '=>' . $ip . ') de l\'équipement :' . $mac);
                $eqLogic->setConfiguration('ip', $ip);
                $changed = true;
            }
            if ($eqLogic->getConfiguration('name') != $logicalName) {
                log::add('neufbox', 'info', 'Mise à jour du nom (' . $eqLogic->getConfiguration('name') . '=>' . $logicalName . ') de l\'équipement :' . $mac);
                $eqLogic->setConfiguration('name', $logicalName);
                $changed = true;
            }
            
            if ($changed)
                $eqLogic->save();
        } else {
            $eqLogic = new self();
            $eqLogic->setLogicalId($mac);
            $eqLogic->setName($logicalName);
            $eqLogic->setEqType_name('neufbox');
            $eqLogic->setConfiguration('name', $logicalName);
            $eqLogic->setConfiguration('ip', $ip);
            $eqLogic->setConfiguration('mac', $mac);
            $eqLogic->setIsVisible(1);
            $eqLogic->setIsEnable(1);
            log::add('neufbox', 'debug', 'before save() saveEquipment =' . $logicalName);
            $eqLogic->save();
        }
        log::add('neufbox', 'debug', 'End saveEquipment =' . $logicalName);
    }

    public static function scanIPdevices()
    {
        return neufbox::detection();
    }

    /* * *********************Méthodes d'instance************************* */
    public function preInsert()
    {}

    public function postInsert()
    {
        // $myConfig = fopen(__DIR__ .'/../../ressources/CONFIG'.$this->getId(), 'w');
        // fclose($myfile);
    }

    public function preSave()
    {}

    public function postSave()
    {
        log::add('neufbox','debug','start postsave Equip :'.$this->getLogicalId());
        $cmd = $this->getCmd(null, 'refresh');
        if (! is_object($cmd)) {
            $cmd = new neufboxCmd();
            $cmd->setLogicalId('refresh');
            $cmd->setIsVisible(1);
        }
        $cmd->setName(__('Rafraichir', __FILE__));
        $cmd->setType('action');
        $cmd->setSubType('other');
        $cmd->setEqLogic_id($this->getId());
        $cmd->save();
        
        if ($this->getConfiguration('ip') == config::byKey('ipBox', 'neufbox')) {
            $cmd = $this->getCmd(null, 'refreshCallhistoryList');
            if (! is_object($cmd)) {
                $cmd = new neufboxCmd();
                $cmd->setLogicalId('refreshCallhistoryList');
                $cmd->setIsVisible(1);
            }
            $cmd->setName(__('refreshCallhistoryList', __FILE__));
            $cmd->setType('action');
            $cmd->setSubType('other');
            $cmd->setEqLogic_id($this->getId());
            $cmd->setConfiguration('request', 'voip.getCallhistoryList');
            $cmd->save();
            
            $cmd = $this->getCmd(null, 'incomingCallhistoryList');
            if (! is_object($cmd)) {
                $cmd = new neufboxCmd();
                $cmd->setLogicalId('incomingCallhistoryList');
                $cmd->setIsVisible(1);
            }
            $cmd->setName(__('incomingCallhistoryList', __FILE__));
            $cmd->setType('info');
            $cmd->setSubType('string');
            $cmd->setEqLogic_id($this->getId());
            $cmd->save();
            
            $cmd = $this->getCmd(null, 'outgoingCallhistoryList');
            if (! is_object($cmd)) {
                $cmd = new neufboxCmd();
                $cmd->setLogicalId('outgoingCallhistoryList');
                $cmd->setIsVisible(1);
            }
            $cmd->setName(__('outgoingCallhistoryList', __FILE__));
            $cmd->setType('info');
            $cmd->setSubType('string');
            $cmd->setEqLogic_id($this->getId());
            $cmd->save();
            
            $cmd = $this->getCmd(null, 'lastIncomingCall');
            if (! is_object($cmd)) {
                $cmd = new neufboxCmd();
                $cmd->setLogicalId('lastIncomingCall');
                $cmd->setIsVisible(1);
            }
            $cmd->setName(__('lastIncomingCall', __FILE__));
            $cmd->setType('info');
            $cmd->setSubType('string');
            $cmd->setEqLogic_id($this->getId());
            $cmd->save();
            
            $cmd = $this->getCmd(null, 'lastIncomingCallRead');
            if (! is_object($cmd)) {
                $cmd = new neufboxCmd();
                $cmd->setLogicalId('lastIncomingCallRead');
                $cmd->setIsVisible(1);
            }
            $cmd->setName(__('lastIncomingCallRead', __FILE__));
            $cmd->setType('info');
            $cmd->setSubType('binary');
            $cmd->setEqLogic_id($this->getId());
            $cmd->save();
            
            $cmd = $this->getCmd(null, 'setLastIncomingCallRead');
            if (! is_object($cmd)) {
                $cmd = new neufboxCmd();
                $cmd->setLogicalId('setLastIncomingCallRead');
                $cmd->setIsVisible(1);
            }
            $cmd->setName(__('setLastIncomingCallRead', __FILE__));
            $cmd->setType('action');
            $cmd->setSubType('other');
            $cmd->setEqLogic_id($this->getId());
            $cmd->save();
            
            $cmd = $this->getCmd(null, 'resetLastIncomingCallRead');
            if (! is_object($cmd)) {
                $cmd = new neufboxCmd();
                $cmd->setLogicalId('resetLastIncomingCallRead');
                $cmd->setIsVisible(1);
            }
            $cmd->setName(__('resetLastIncomingCallRead', __FILE__));
            $cmd->setType('action');
            $cmd->setSubType('other');
            $cmd->setEqLogic_id($this->getId());
            $cmd->save();
            
            $cmd = $this->getCmd(null, 'internetStatus');
            if (! is_object($cmd)) {
                $cmd = new neufboxCmd();
                $cmd->setLogicalId('internetStatus');
                $cmd->setIsVisible(1);
            }
            $cmd->setName(__('internetStatus', __FILE__));
            $cmd->setType('info');
            $cmd->setSubType('binary');
            $cmd->setEqLogic_id($this->getId());
            $cmd->save();
            
            $cmd = $this->getCmd(null, 'reboot');
            if (! is_object($cmd)) {
                $cmd = new neufboxCmd();
                $cmd->setLogicalId('reboot');
                $cmd->setIsVisible(1);
            }
            $cmd->setName(__('reboot', __FILE__));
            $cmd->setType('action');
            $cmd->setSubType('other');
            $cmd->setEqLogic_id($this->getId());
            $cmd->setConfiguration('request', 'system.reboot');
            $cmd->setConfiguration('post', true);
            $cmd->save();
            
            $cmd = $this->getCmd(null, 'voipRestart');
            if (! is_object($cmd)) {
                $cmd = new neufboxCmd();
                $cmd->setLogicalId('voipRestart');
                $cmd->setIsVisible(1);
            }
            $cmd->setName(__('voipRestart', __FILE__));
            $cmd->setType('action');
            $cmd->setSubType('other');
            $cmd->setEqLogic_id($this->getId());
            $cmd->setConfiguration('request', 'voip.restart');
            $cmd->setConfiguration('post', true);
            $cmd->save();
            
            $cmd = $this->getCmd(null, 'ddnsDisable');
            if (! is_object($cmd)) {
                $cmd = new neufboxCmd();
                $cmd->setLogicalId('ddnsDisable');
                $cmd->setIsVisible(1);
            }
            $cmd->setName(__('ddnsDisable', __FILE__));
            $cmd->setType('action');
            $cmd->setSubType('other');
            $cmd->setEqLogic_id($this->getId());
            $cmd->setConfiguration('request', 'ddns.disable');
            $cmd->setConfiguration('post', true);
            $cmd->save();
            
            $cmd = $this->getCmd(null, 'ddnsEnable');
            if (! is_object($cmd)) {
                $cmd = new neufboxCmd();
                $cmd->setLogicalId('ddnsEnable');
                $cmd->setIsVisible(1);
            }
            $cmd->setName(__('ddnsEnable', __FILE__));
            $cmd->setType('action');
            $cmd->setSubType('other');
            $cmd->setEqLogic_id($this->getId());
            $cmd->setConfiguration('request', 'ddns.enable');
            $cmd->setConfiguration('post', true);
            $cmd->save();
        } else {
            foreach (array(
                'name',
                'ip',
                'iface',
                'status',
                'active',
                'online',
                'offline',
                'timer'
            ) as $key) {
                $neufboxCmd = $this->getCmd(null, $key);
                if (! is_object($neufboxCmd)) {
                    $neufboxCmd = new neufboxCmd();
                    $neufboxCmd->setLogicalId($key);
                    $neufboxCmd->setIsVisible(1);
                }
                $neufboxCmd->setConfiguration($key, $key);
                $neufboxCmd->setName(__($key, __FILE__));
                $neufboxCmd->setType('info');
                if ($key == 'active') {
                    $neufboxCmd->setSubType('binary');
                    $neufboxCmd->setIsHistorized(1);
                } else {
                    $neufboxCmd->setSubType('other');
                }
                $neufboxCmd->setEqLogic_id($this->getId());
                $neufboxCmd->save();
            }
        }
        // log::add('neufbox','debug','end postsave Equip');
    }

    public function preUpdate()
    {}

    public function postUpdate()
    {}

    public function preRemove()
    {
        // TODO est-ce encore utile ?
        @unlink(__DIR__ . '/../../ressources/CONFIG' . $this->getId());
    }

    public function postRemove()
    {}

    public function updateConfig()
    { // MAJ du fichier CONFIG après la sauvegarde des commandes
        $myConfig = fopen(__DIR__ . '/../../ressources/CONFIG' . $this->getId(), 'w');
        if ($myConfig) {
            foreach ($this->getCmd('action') as $cmd) {
                if ($cmd->getLogicalId() != 'refresh') {
                    $line = $cmd->getConfiguration('mac') . " " . $cmd->getConfiguration('keepalive') . "\n";
                    fwrite($myConfig, $line);
                }
            }
            fclose($myConfig);
        }
    }
    
    /* * ******* refresh all except box itself **** */
    private function refresh_all($parsed_json)
    {
        log::add('neufbox', 'debug', __('refresh all equipments neufbox start ', __FILE__));
        $change = false;
        foreach (eqLogic::byType('neufbox') as $eqLogic) {
            //log::add('neufbox', 'debug', __('refresh? equipment neufbox start '.$eqLogic->getConfiguration('ip').' -- '.$eqLogic->getConfiguration('mac'), __FILE__));
            if ($eqLogic->getConfiguration('ip') !== config::byKey('ipBox', 'neufbox')) {
                if (/*$eqLogic->getIsEnable() &&*/ $eqLogic->getConfiguration('ip') !== config::byKey('ipBox', 'neufbox')) {
                    try {
                        if ($this->refreshEqLogic($eqLogic,$parsed_json)) $change = true;
                    } catch (Exception $exc) {
                        log::add('neufbox', 'error', __('Erreur pour ', __FILE__) . $eqLogic->getHumanName() . ' : ' . $exc->getMessage());
                    }
                }
            }
        }
        log::add('neufbox', 'debug', __('refresh all equipments neufbox end ', __FILE__));
        return $change;
    }
    
    private function refreshEqLogic($eqLogic,$parsed_json)
    {
        
        $change = false;
        $mac = $eqLogic->getConfiguration('mac');
        //log::add('neufbox', 'debug', __('refresh equipment neufbox start '.$mac, __FILE__));
        if (! isset($parsed_json->{$mac})) {
            log::add('neufbox', 'warning', '** eqLogic name= ' . $eqLogic->getName() . ' refresh failed mac not found in json stream ! ' . $mac);
            foreach ($eqLogic->getCmd('info') as $cmd) {
                //TODO
                continue;
                $value = "unknown";
                if ($cmd->execCmd() !== $value) {
                    log::add('neufbox', 'debug', 'refresh info cmd:' . $cmd->getLogicalId() . ' - value =' . $value . ' - mac =' . $mac);
                    $cmd->event($value);
                    $change = true;
                }
            }
        } else {
            foreach ($eqLogic->getCmd('info') as $cmd) {
                $value = $parsed_json->{$mac}->{$cmd->getLogicalId()};
                //log::add('neufbox', 'debug', 'refresh info cmd:' . $cmd->getLogicalId() . ' - old value =' . $cmd->execCmd() . ' - mac =' . $mac);
                // if ($cmd->getValue() != $value) {
                if ($cmd->execCmd() !== $cmd->formatValue($value)) {
                //if ($cmd->execCmd() != $value) {
                    log::add('neufbox', 'debug', 'refresh info cmd:' . $cmd->getLogicalId() . ' - new value =' . $value . ' - mac =' . $mac);
                    // $cmd->setValue($value);
                    // $cmd->save();
                    // $cmd->setCollectDate('');
                    $cmd->event($value);
                    $change = true;
                }
            }
        }
        return $change;
    }        

    public function refresh()
    {
        $change = false;
        $mac = $this->getConfiguration('mac');
        log::add('neufbox', 'debug', '******** Début du refresh de l\'équipement ********: ' . $this->getName(). ' ** eqLogic mac= ' . $mac);
        /*$ip = config::byKey('ipBox', 'neufbox');
        $json = shell_exec(__DIR__ . '/../../ressources/apineufbox.py update ' . $ip); // Execute le script python et récupère le json
        $parsed_json = json_decode($json);
        */
        $parsed_json = neufbox::queryBox();
        
        if ($this->getConfiguration('ip') == config::byKey('ipBox', 'neufbox')) {
            $cmd = $this->getCmd(null, 'refreshCallhistoryList');
            if (is_object($cmd)) {
                $cmd->execCmd();
            } else {
                log::add('neufbox', 'warning', '** eqLogic name= ' . $this->getName() . ' has no cmd refreshCallhistoryList !');
            }
            $change = $this->refresh_all($parsed_json);
            $status = (neufbox::queryWanStatusBox()=='up'?1:0);
            $cmd = $this->getCmd(null, 'internetStatus');
            if (is_object($cmd)) {
                $value = $cmd->execCmd();
            } else {
                log::add('neufbox', 'warning', '** eqLogic name= ' . $this->getName() . ' has no cmd internetStatus !');
            }
            if ($value != $status) {
                log::add('neufbox', 'info', '** eqLogic name= ' . $this->getName() . 'wan status changed ='.$status);
                $cmd->event(($status));
            }
        } else {
            $change = $this->refreshEqLogic($this,$parsed_json);
        }
        
        if ($change) $this->refreshWidget();
    }

    public function toHtml($_version = 'dashboard')
    {
        $replace = $this->preToHtml($_version);
        if (! is_array($replace)) {
            return $replace;
        }
        $_version = jeedom::versionAlias($_version);
        $id = $this->getId();
        
        if ($_version != 'mobile') { // Version Dashboard
            $colorDark = 'color: rgb(0,0,0)';
            $body = null;
            $refresh = $this->getCmd(null, 'refresh');
            $replace['#refresh_id#'] = is_object($refresh) ? $refresh->getId() : '';
            $configuration = $this->getConfiguration();
            $tdTheadIP = null;
            if ($configuration['displayIp']) {
                $tdTheadIp = '<th>Adresse IP</th>';
            }
            $tdTheadMac = null;
            if ($configuration['displayMac']) {
                $tdTheadMac = '<th>Adresse MAC</th>';
            }
            $tdTheadHostname = null;
            if ($configuration['displayHostname']) {
                $tdTheadHostname = '<th>Hostname</th>';
            }
            $thead = '<th>Nom</th><th>Status</th><th>Online</th><th>Offline</th>' . $tdTheadIp . $tdTheadMac . $tdTheadHostname;
            if ($this->getConfiguration('ip') == config::byKey('ipBox', 'neufbox')) {
                $cmd_id = $this->getID();
                $name = $this->getName();
                $status = $this->getCmd(null, 'internetStatus')->execCmd();
                $ifaceIcon = '';
                if ($status ) {
                    $statusIcon = 'fa-check';
                    $statusStyle = '';
                } else {
                    $statusIcon = 'fa-ban';
                    $statusStyle = $colorDark;
                }
                //$divStatus = '<div class="history fa ' . $statusIcon . ' fa-1" data-type="info" data-subtype="binary" data-cmd_id="' . $this->searchCmdByConfiguration('internetStatus', 'info')[0]->getId() . '" style="margin: 5px;' . $statusStyle . '"></div>';
                $divStatus = '<div class="history fa ' . $statusIcon . ' fa-1" data-type="info" data-subtype="binary" data-cmd_id="' . "123456" . '" style="margin: 5px;' . $statusStyle . '"></div>';
                $tdBodyStatus = '<td style="text-align: center;">' . $divStatus  . '</td>';
                
                $online = '';
                $timer = '';
                $offline = '';
                $tdBodyIp = '';
                $tdBodyMac = '';
                $tdBodyHostname = '';
                
                $body = '<tr id="' . $cmd_id . '">
                                  <td><span class="fa ' . $ifaceIcon . ' fa-1" style="margin-right: 5px;' . $statusStyle . ';"></span><span style="' . $statusStyle . ';">' . $name . '</span></td>
                                  ' . $tdBodyStatus . '
                                  <td><span style="' . $statusStyle . ';">' . $online . '</span><span style="font-size: 70%; margin: 2px; vertical-align: bottom;">' . $timer . '</span></td>
                                  <td><span style="' . $statusStyle . ';">' . $offline . '</span></td>
                                  ' . $tdBodyIp . '
                                  ' . $tdBodyMac . '
                                  ' . $tdBodyHostname . '
                                  </tr>';
                
            }
            
            $eqLogics = eqLogic::byType('neufbox');
            foreach ($eqLogics as $eqLogic) {
                if ($this->getConfiguration('ip') == config::byKey('ipBox', 'neufbox')) {
                    if ($eqLogic->getConfiguration('ip') == config::byKey('ipBox', 'neufbox')) {
                        continue;
                    }
                } else {
                    //continue;
                    
                    if ($eqLogic->getName() !== $this->getName()) continue;
                    /*
                    if (!isset($eqLogic->searchCmdByConfiguration('ip', 'info')[0])) continue;
                    if ($eqLogic->searchCmdByConfiguration('ip', 'info')[0]->execCmd() != $this->getConfiguration('ip')) {
                        continue;
                    }
                    */
                }
                
                $cmd_id = $eqLogic->getID();
                $name = $eqLogic->getName();
                $status = $eqLogic->searchCmdByConfiguration('status', 'info')[0]->execCmd();
                if ($status == 'online') {
                    $statusIcon = 'fa-check';
                    $statusStyle = '';
                } else {
                    $statusIcon = 'fa-ban';
                    $statusStyle = $colorDark;
                }
                $active = $eqLogic->searchCmdByConfiguration('active', 'info')[0]->execCmd();
                if ($active) {
                    $activeIcon = 'fa-eye';
                    $activeStyleOff = $colorDark;
                    $activeStyleOn = '';
                    if ($configuration['displayTimer']) {
                        $timer = $eqLogic->searchCmdByConfiguration('timer', 'info')[0]->execCmd();
                        $timer = '-' . gmdate("i:s", (int) $timer);
                    }
                } else {
                    $activeIcon = 'fa-eye-slash';
                    $activeStyleOff = '';
                    $activeStyleOn = $colorDark;
                    $timer = '';
                }
                $iface = $eqLogic->searchCmdByConfiguration('iface', 'info')[0]->execCmd();
                $ifaceIcon = '';
                if (preg_match('/^wlan/', $iface)) {
                    $ifaceIcon = 'fa-wifi';
                } elseif (preg_match('/^lan/', $iface)) {
                    $ifaceIcon = 'fa-plug';
                }
                $online = $eqLogic->searchCmdByConfiguration('online', 'info')[0]->execCmd();
                                
                $mois = floor($online/3600/24/30);
                $jour = floor(($online-($mois*3600*24*30))/3600/24);
                $heure = floor((($online-($mois*3600*24*30))-($jour*3600*24))/3600);
                //log::add('neufbox', 'debug', '** online sec= ' . $online . ' mois ='.$mois.' jour ='.$jour.' heure='.$heure);
                
                $temp=$mois.'m '.$jour.'j '.$heure.'h:'.gmdate("i:s",(int) $online);
                $temp=($mois?$mois.'m ':'');
                $temp.=($jour?$jour.'j ':'');
                $temp.=($heure?$heure.':':'').gmdate("i:s",(int) $online);
                $online=$temp;
                
                
                $offline = $eqLogic->searchCmdByConfiguration('offline', 'info')[0]->execCmd();
                $mois = floor($offline/3600/24/30);
                $jour = floor(($offline-($mois*3600*24*30))/3600/24);
                $heure = floor((($offline-($mois*3600*24*30))-($jour*3600*24))/3600);
                //log::add('neufbox', 'debug', '** offline sec= ' . $offline . ' mois ='.$mois.' jour ='.$jour.' heure='.$heure);
                
                $temp=$mois.'m '.$jour.'j '.$heure.'h:'.gmdate("i:s",(int) $offline);
                $temp=($mois?$mois.'m ':'');
                $temp.=($jour?$jour.'j ':'');
                $temp.=($heure?$heure.':':'').gmdate("i:s",(int) $offline);
                $offline=$temp;
                
                $divStatus = '<div class="history fa ' . $statusIcon . ' fa-1" data-type="info" data-subtype="binary" data-cmd_id="' . $eqLogic->searchCmdByConfiguration('status', 'info')[0]->getId() . '" style="margin: 5px;' . $statusStyle . '"></div>';
                $divActive = '<div class="history fa ' . $activeIcon . ' fa-1" data-type="info" data-subtype="binary" data-cmd_id="' . $eqLogic->searchCmdByConfiguration('active', 'info')[0]->getId() . '" style="margin: 5px; ' . $activeStyleOn . '"></div>';
                $tdBodyStatus = '<td style="text-align: center;">' . $divStatus . $divActive . '</td>';
                
                $tdBodyIp = '';
                if ($configuration['displayIp']) {
                    $ip = $eqLogic->searchCmdByConfiguration('ip', 'info')[0]->execCmd();
                    $tdBodyIp = '<td><span style="' . $statusStyle . ';">' . $ip . '</span></td>';
                }
                $tdBodyMac = '';
                if ($configuration['displayMac']) {
                    $mac = $eqLogic->getConfiguration('mac');
                    $tdBodyMac = '<td><span style="' . $statusStyle . ';">' . $mac . '</span></td>';
                }
                $tdBodyHostname = '';
                if ($configuration['displayHostname']) {
                    $hostname = $eqLogic->searchCmdByConfiguration('name', 'info')[0]->execCmd();
                    $tdBodyHostname = '<td><span style="' . $statusStyle . ';">' . $hostname . '</span></td>';
                }
                $body .= '<tr id="' . $cmd_id . '">
                                  <td><span class="fa ' . $ifaceIcon . ' fa-1" style="margin-right: 5px;' . $statusStyle . ';"></span><span style="' . $statusStyle . ';">' . $name . '</span></td>
                                  ' . $tdBodyStatus . '
                                  <td><span style="' . $statusStyle . ';">' . $online . '</span><span style="font-size: 70%; margin: 2px; vertical-align: bottom;">' . $timer . '</span></td>
                                  <td><span style="' . $statusStyle . ';">' . $offline . '</span></td>
                                  ' . $tdBodyIp . '
                                  ' . $tdBodyMac . '
                                  ' . $tdBodyHostname . '
                                  </tr>';
            }
            $replace['#thead#'] = $thead;
            $replace['#body#'] = $body;
        } else { // Version mobile
            $refresh = $this->getCmd(null, 'refresh');
            $replace['#refresh_id#'] = is_object($refresh) ? $refresh->getId() : '';
            $thead = '<th>Nom</th><th>Status</th><th>Online</th><th>Offline</th>';
            foreach ($this->getCmd('action') as $cmd) { // Creation du body
                if ($cmd->getLogicalId() == 'refresh' || ! $cmd->getIsVisible()) {
                    continue;
                }
                $cmd_id = $cmd->getID();
                $name = $cmd->getName();
                $status = $this->getCmd(null, $cmd->getId() . '_status')
                    ->execCmd();
                $active = $this->getCmd(null, $cmd->getId() . '_active')
                    ->execCmd();
                $style = null;
                if ($status == 'online') {
                    $statusIcon = 'fa-check';
                    $counter = $this->getCmd(null, $cmd->getId() . '_online')
                        ->execCmd();
                } elseif ($active) {
                    $statusIcon = 'fa-eye';
                    $counter = $this->getCmd(null, $cmd->getId() . '_online')
                        ->execCmd();
                } else {
                    $statusIcon = 'fa-eye-slash';
                    $counter = $this->getCmd(null, $cmd->getId() . '_offline')
                        ->execCmd();
                    $style = 'color: rgb(50,50,50)';
                }
                if ($counter > 3599999) {
                    $counter = 3599999;
                }
                if (empty($counter)) {} elseif (mb_strlen(floor($counter / 3600)) == 1) {
                    $counter = '0' . floor($counter / 3600) . ':' . gmdate("i:s", $counter);
                } else {
                    $counter = floor($counter / 3600) . ':' . gmdate("i:s", $counter);
                }
                $content .= '<span id="' . $cmd_id . '" class="fa ' . $statusIcon . ' fa-1" style="center; ' . $style . ';"></span><span style="' . $style . ';"> ' . $name . ' </span><br/>
                               <span style="padding-left: 20px; ' . $style . ';">' . $counter . '</span><br/>';
            }
            $replace['#content#'] = $content;
        }
        return template_replace($replace, getTemplate('core', $_version, 'neufbox', 'neufbox'));
    }
    
    /* * **********************Getteur Setteur*************************** */
}

class neufboxCmd extends cmd
{

    /* * *************************Attributs****************************** */
    
    /* * ***********************Methode static*************************** */
    
    /* * *********************Methode d'instance************************* */
    public function createSubCmd()
    {
        return;
        foreach (array(
            'name',
            'ip',
            'iface',
            'status',
            'active',
            'online',
            'offline',
            'timer'
        ) as $key) {
            $neufboxCmd = $this->getEqLogic()->getCmd(null, $this->getId() . '_' . $key);
            if (! is_object($neufboxCmd)) {
                $neufboxCmd = new neufboxCmd();
            }
            $neufboxCmd->setName($this->getName() . '_' . $key);
            $neufboxCmd->setLogicalId($this->getId() . '_' . $key);
            $neufboxCmd->setEqLogic_id($this->getEqLogic_id());
            $neufboxCmd->setType('info');
            if ($key == 'active') {
                $neufboxCmd->setSubType('binary');
                $neufboxCmd->setIsHistorized(1);
            } else {
                $neufboxCmd->setSubType('other');
            }
            $neufboxCmd->setConfiguration('mac', $this->getConfiguration('mac'));
            $neufboxCmd->setConfiguration('info', $key);
            $neufboxCmd->setConfiguration('name', $this->getName());
            $neufboxCmd->save();
        }
    }

    public function preSave()
    {}

    public function postSave()
    { // MAJ du fichier CONFIG
        return;
        log::add('neufbox', 'debug', 'start postsave CMD :' . $this->getLogicalId());
        
        if ($this->getLogicalId() == 'refresh') {
            return;
        }
        if ($this->getType() == 'action') {
            $this->getEqLogic()->updateConfig();
            $this->createSubCmd();
        }
        if ($this->getType() == 'info') {
            $oldName = $this->getName();
            $newName = $this->getConfiguration('name') . '_' . $this->getConfiguration('info');
            if ($oldName != $newName) {
                $this->setName($newName);
                $this->save();
            }
        }
        log::add('neufbox', 'debug', 'end postsave CMD');
    }

    public function postRemove()
    { // Si cmd action -> suppresssion de ses commandes info
        return;
        if ($this->getType() == 'action') {
            foreach ($this->getEqLogic()->getCmd('info') as $cmd) {
                if ($this->getConfiguration('mac') == $cmd->getConfiguration('mac')) {
                    $cmd->remove();
                }
            }
        }
        $this->getEqLogic()->updateConfig();
    }

    public function dontRemoveCmd()
    { // Ne pas supprimer refresh et les cmd info
        return;
        if ($this->getLogicalId() == 'refresh' || $this->getType() == 'info') {
            return true;
        }
        return false;
    }

    public function execute($_options = array())
    {
        log::add('neufbox', 'debug', 'execute action = ' . $this->getLogicalId());
        if ($this->getLogicalId() == 'refresh') {
            $this->getEqLogic()->refresh();
            return;
        }
        
        //if ($this->getLogicalId() == 'refreshCallhistoryList') {
        if ($this->getConfiguration('request') !== '') {
            log::add('neufbox','debug','execute action call '.$this->getName());
            $ip = config::byKey('ipbox', 'neufbox');
            $request = 'http://' . $ip . '/api/1.0/?method=auth.getToken';
            $request = new com_http($request);
            $xmlstr = $request->exec(5, 1);
            
            $neufbox = new SimpleXMLElement($xmlstr);
            log::add('neufbox', 'debug', 'get token = ' . $neufbox->auth['token']);
            
            $login = config::byKey('neufboxLogin', 'neufbox');
            $password = config::byKey('neufboxPassword', 'neufbox');
            $token = $neufbox->auth['token'];
            $hash = hash_hmac('sha256', hash('sha256', $login), $token) . hash_hmac('sha256', hash('sha256', $password), $token);
            // log::add('neufbox','debug','hash = '.$hash);
            $request = 'http://' . $ip . '/api/1.0/?method=auth.checkToken&token=' . $neufbox->auth['token'] . '&hash=' . $hash;
            $request = new com_http($request);
            $xmlstr = $request->exec(5, 1);
            
            log::add('neufbox', 'debug', 'check token = ' . $xmlstr);
            
            $request = 'http://' . $ip . '/api/1.0/?method='. $this->getConfiguration('request') .'&token=' . $neufbox->auth['token'];
            $request = new com_http($request);
            if ($this->getConfiguration('post')) $request->setPost(true);
            $xmlstr = $request->exec(5, 1);
            log::add('neufbox', 'debug', 'end '.$this->getName() .' = ' . $xmlstr);
            
            $rsp = new SimpleXMLElement($xmlstr);
            if ($rsp['stat'] != 'ok') {
                /*
                 * <rsp stat="fail" version="1.0">   <err code="112" msg="Method not found" /> </rsp>
                 */
            
                log::add('neufbox', 'error', 'request  '.$this->getName() . ' rsp = '. $rsp['stat'] . ' error code = ' . $rsp->{'err'}['code'] .' error msg = ' . $rsp->{'err'}['msg'].' txt=' . $xmlstr);
            } else {
                switch ($this->getName()) {
                    case  'refreshCallhistoryList' :
                        $i = 0;
                        /*
                         * setlocale(LC_TIME, "fr_FR");
                         * date_default_timezone_set('Europe/Paris');
                         * // --- La setlocale() fonctionnne pour strftime mais pas pour DateTime->format()
                         * setlocale(LC_TIME, 'fr_FR.utf8','fra');// OK
                         * // strftime("jourEnLettres jour moisEnLettres annee") de la date courante
                         * log::add('neufbox','debug','Date du jour : ', strftime("%A %d %B %Y"));
                         */
                        $incomingCalls = array();
                        $outgoingCalls = array();
                        foreach ($rsp->{'calls'}->children() as $call) {
                            // log::add('neufbox','debug','appel = '.$call['direction'].' - '.$call['number'].' - '.$call['length'].' - '.date("D j M h:i:s",(int)$call['date']));
                            if ($call['direction'] == 'incoming') {
                                $incomingCalls[] = array(
                                    'number' => $call['number'],
                                    'length' => $call['length'],
                                    'date' => (int) $call['date']
                                );
                            } else {
                                $outgoingCalls[] = array(
                                    'number' => $call['number'],
                                    'length' => $call['length'],
                                    'date' => (int) $call['date']
                                );
                            }
                            $i ++;
                        }
                        
                        // log::add('neufbox','debug','nbr d\appels = '.$i);
                        // <call type="voip" direction="incoming" number="024054 XXXX" length="259" date="1508519150" />
                        $value = "";
                        
                        $lastDate = 0;
                        $lastCall = '';
                        setlocale(LC_TIME, 'fr_FR.utf8','fra');
                        foreach ($incomingCalls as $call) {
                            $line = $call['number'] . ' : ' . $call['length'] . 's le ' . strftime("%A %d %B à %k heures %M", $call['date']);
                            if ($lastDate < $call['date']) {
                                $lastDate = $call['date'];
                                $lastCall = strftime("%A %d %B à %k heures %M", $call['date']). ' du '.rtrim($call['number'],'X').' pendant '.($call['length']>60?round($call['length']/60) .' minutes':$call['length'].' secondes');
                            }
                            $value .= $call['number'] . ' : ' . $call['length'] . 's le ' . strftime("%A %d %B à %k heures %M", $call['date']) . "\n";
                        }
                        
                        $cmd = $this->getEqLogic()->getCmd('info', 'lastIncomingCall');
                        if ($cmd->getValue() != $lastCall) {
                            $cmd->setValue($lastCall);
                            $cmd->save();
                            $cmd->setCollectDate($lastDate);
                            $cmd->event($lastCall);
                            $cmd = $this->getEqLogic()->getCmd('info', 'lastIncomingCallRead');
                            $cmd->event(false);
                        }
                        
                        log::add('neufbox', 'debug', 'appels entrants = ' . $value);
                        $cmd = $this->getEqLogic()->getCmd('info', 'incomingCallhistoryList');
                        if ($cmd->getValue() != $value) {
                            $cmd->setValue($value);
                            $cmd->save();
                            $cmd->setCollectDate('');
                            $cmd->event($value);
                        }
                        $value = "";
                        foreach ($outgoingCalls as $call) {
                            $value .= $call['number'] . ' : ' . $call['length'] . 's le ' . date("D j M H:i:s", $call['date']) . "\n";
                        }
                        log::add('neufbox', 'debug', 'appels sortants = ' . $value);
                        $cmd = $this->getEqLogic()->getCmd('info', 'outgoingCallhistoryList');
                        if ($cmd->getValue() != $value) {
                            $cmd->setValue($value);
                            $cmd->save();
                            $cmd->setCollectDate('');
                            $cmd->event($value);
                        }
                
                        if ($this->getLogicalId() == 'setLastIncomingCallRead') {
                            $cmd = $this->getEqLogic()->getCmd('info', 'lastIncomingCallRead');
                            $cmd->event(true);
                        }
                        if ($this->getLogicalId() == 'resetLastIncomingCallRead') {
                            $cmd = $this->getEqLogic()->getCmd('info', 'lastIncomingCallRead');
                            $cmd->event(false);
                        }
                        
                        break;
                    case 'reboot' :
                        log::add('neufbox', 'debug', 'ici '.$this->getName() .' = ' . $xmlstr);
                        break;
                    default :
                        log::add('neufbox', 'debug', 'la '.$this->getName() .' = ' . $xmlstr);
                        
                        
                }//end switch
            }//end if ($rsp['stat'] == 'fail') {
        }//end if ($this->getConfiguration('request') !== '') {
            
            
        // log::add('neufbox','debug','execute');
    }
    
    /* * **********************Getteur Setteur*************************** */
}

?>
