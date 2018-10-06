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

try {
    require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
    include_file('core', 'authentification', 'php');

    if (!isConnect('admin')) {
        throw new Exception(__('401 - Accès non autorisé', __FILE__));
    }
    
    if (init('action') == 'scanIPdevices') {
        $return = neufbox::scanIPdevices();
        
        if ($return[0]) {
            ajax::success($return[1]);
        } else {
            ajax::error($return[1]);
        }
    }
    
    if (init('action') == 'getMacAddress') {
        if (init('ipbox') == '') {
            log::add('neufbox', 'debug', 'neufbox.ajax.php ipbox missing !');
            $ipAddress='192.168.0.1';
        } else {
            log::add('neufbox', 'debug', 'neufbox.ajax.php ip= : '.init('ipbox'));
            $ipAddress=init('ipbox');
        }
        $macAddr=false;
        
        #run the external command, break output into lines
        $arp=`arp -a $ipAddress`;
        $lines=explode("\n", $arp);
        
        #look for the output line describing our IP address
        //box (192.168.0.1) at 44:ce:7d:15:75:98 [ether] on eth0
        foreach($lines as $line)
        {
            //$cols=preg_split('/\s+/', trim($line));
            $cols=explode(' ', trim($line));
            if ($cols[1]=='('.$ipAddress.')') {
                $macAddr=$cols[3];
                $name=($cols[0]?$cols[0]:'mybox');
            } else {
                log::add('neufbox', 'debug', 'neufbox.ajax.php ip addr not found in arp request = : '.$line);
            }
        }
        if (empty($name)) {
            log::add('neufbox', 'debug', 'neufbox.ajax.php name of box not found forced mybox');
            $name = 'mybox';
        }
        config::save('nameBox', $name,'neufbox');
        if ($macAddr) {
            config::save('macBox', $macAddr,'neufbox');
            config::save('nameBox', $name,'neufbox');
            ajax::success($macAddr);
        } else {
            ajax::error();
        }
    }   
    
    if (init('action') == 'removeAll') {
        $return = neufbox::removeAll();
        
        if ($return[0]) {
            ajax::success($return[1]);
        } else {
            ajax::error($return[1]);
        }
    }
    
    log::add('neufbox', 'debug', 'neufbox.ajax.php action : '.init('action'));
    throw new Exception(__('Aucune methode correspondante à : ', __FILE__) . init('action'));
    /*     * *********Catch exeption*************** */
} catch (Exception $e) {
    ajax::error(displayExeption($e), $e->getCode());
}
?>
