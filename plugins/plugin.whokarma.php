<?php

/**
 * @name plugin.whokarma.php
 * @date 20-02-2012
 * @version v0.1.2
 * @website www.klaversma.eu
 *
 * @author Max "TheM" Klaversma
 * @copyright 2010 - 2012
 *
 * Original made for XAseco1/TrackMania Forever by Milenco.
 *
 * ---------------------------------------------------------------------
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 * You are allowed to change things or use this in other projects, as
 * long as you leave the information at the top (name, date, version,
 * website, package, author, copyright) and publish the code under
 * the GNU General Public License version 3.
 * ---------------------------------------------------------------------
 */

class WhoKarma {
	private $Aseco;
	private $version;

	function onInit($aseco) {
		$this->Aseco = $aseco;
		$this->version = '0.1.2';

		// Register this to the global version pool (for up-to-date checks)
		$this->Aseco->plugin_versions[] = array(
			'plugin'   => 'plugin.whokarma.php',
			'author'   => 'TheM',
			'version'   => $this->version
		);

		$rasp = false;

		foreach($this->Aseco->plugins as &$installed_plugin) {
			if('plugin.rasp_karma.php' == $installed_plugin) {
				$rasp = true;
			}
		}

		if($rasp == false) {
			trigger_error('WhoKarma plugin does only support RASP Karma, enable that or disable this !', E_USER_ERROR);
		}
	}

	function chat_whokarma($aseco, $command) {
		$this->Aseco = $aseco;

		$player = $command['author'];
		$login = $player->login;
		$mapid = $this->Aseco->server->map->id;

		//Get list ++ voters
		$sql = "SELECT PlayerId FROM rs_karma WHERE Score = '1' AND `MapId` = '".$mapid."'";
		$res = mysql_query($sql);
		$counter = 0;
		//Put ++ voters in array with correct nickname and number of votes
		while ($row = mysql_fetch_row($res)) {
			//Retrieve player nickname based on playerID
			$sql = "SELECT NickName FROM players WHERE Id = '".$row[0]."'";
			$res2 = mysql_query($sql);
			$row2 = mysql_fetch_row($res2);
			$nickname = $row2[0];
			mysql_free_result($res2);

			//Adding information to array
			$db[$counter]['nickname'] = $nickname;
			$db[$counter]['vote'] = "++";
			$counter++;
		}
		mysql_free_result($res);

		//Get list -- voters
		$sql = "SELECT PlayerId FROM rs_karma WHERE Score = '-1' AND `MapId` = '".$mapid."'";
		$res = mysql_query($sql);
		//Put ++ voters in array with correct nickname and number of votes
		while ($row = mysql_fetch_row($res)) {
			//Retrieve player nickname based on playerID
			$sql = "SELECT NickName FROM players WHERE Id = '".$row[0]."'";
			$res2 = mysql_query($sql);
			$row2 = mysql_fetch_row($res2);
			$nickname = $row2[0];
			mysql_free_result($res2);

			//Adding information to array
			$db[$counter]['nickname'] = $nickname;
			$db[$counter]['vote'] = "--";
			$counter++;
		}
		mysql_free_result($res);

		$head = 'WhoKarma v'.$this->version.' - Current track voters:';
		$msg = array();
		$lines = 0;
		$player->msgs = array();
		$player->msgs[0] = array(1, $head, array(1.2, 0.1, 0.45, 0.2, 0.2, 0.15), array('Icons128x128_1', 'LoadTrack', 0.02));
		if ($total = count($db)) {
			$msg[] = array('Nr.', 'Player',	'Vote');

			for ($i = 0; $i < $total; $i++) {
				//Read nickname from array
				$nickname = $db[$i]['nickname'];
				$vote = $db[$i]['vote'];

				//Adding line to the ManiaLink window
				$msg[] = array(str_pad($i+1, 2, '0', STR_PAD_LEFT) . '.',
				'{#black}' . $nickname,
				$vote);

				$lines++;
				if ($lines > 14) {
					$player->msgs[] = $msg;
					$lines = 0;
					$msg = array();
				}
			}
			// add if last batch exists
			if (!empty($msg))
				$player->msgs[] = $msg;

			// display ManiaLink message
			display_manialink_multi($player);

		} else {
			$this->Aseco->client->query('ChatSendServerMessageToLogin', $this->Aseco->formatColors('{#server}> {#error}No votes found!'), $login);
		}
	}
}

Aseco::registerEvent('onSync', 'whokarma_onSync');
Aseco::addChatCommand('whokarma', 'Shows who votes what on the current map.');

global $whokarma;
$whokarma = new WhoKarma();

function whokarma_onSync($aseco) {
	global $whokarma;
	$whokarma->onInit($aseco);
}

function chat_whokarma($aseco, $command) {
	global $whokarma;
	$whokarma->chat_whokarma($aseco, $command);
}
?>