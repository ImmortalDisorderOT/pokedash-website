<?php require_once 'engine/init.php'; include 'layout/overall/header.php';
 
if ($config['log_ip']) {
	znote_visitor_insert_detailed_data(4);
}

if (isset($_GET['name']) === true && empty($_GET['name']) === false) {
	$name = getValue($_GET['name']);
	$user_id = user_character_exist($name);
	
	if ($user_id !== false) {
		$loadOutfits = $config['show_outfits']['characterprofile'];

		if ($config['TFSVersion'] == 'TFS_10') {
			if (!$loadOutfits) {
				$profile_data = user_character_data($user_id, 'account_id', 'name', 'level', 'group_id', 'vocation', 'health', 'healthmax', 'experience', 'mana', 'manamax', 'sex', 'lastlogin');
			} else { // Load outfits
				$profile_data = user_character_data($user_id, 'account_id', 'name', 'level', 'group_id', 'vocation', 'health', 'healthmax', 'experience', 'mana', 'manamax', 'sex', 'lastlogin', 'lookbody', 'lookfeet', 'lookhead', 'looklegs', 'looktype', 'lookaddons');
			}
			$profile_data['online'] = user_is_online_10($user_id);
			
			if ($config['Ach']) {
				$user_id = (int) $user_id;
				$achievementPoints = mysql_select_single("SELECT SUM(`value`) AS `sum` FROM `player_storage` WHERE `key` LIKE '30___' AND `player_id`=$user_id");
			}
		} else { // TFS 0.2, 0.3
			if (!$loadOutfits) {
				$profile_data = user_character_data($user_id, 'name', 'account_id', 'level', 'group_id', 'vocation', 'health', 'healthmax', 'experience', 'mana', 'manamax', 'lastlogin', 'online', 'sex');
			} else { // Load outfits
				$profile_data = user_character_data($user_id, 'name', 'account_id', 'level', 'group_id', 'vocation', 'health', 'healthmax', 'experience', 'mana', 'manamax', 'lastlogin', 'online', 'sex', 'lookbody', 'lookfeet', 'lookhead', 'looklegs', 'looktype', 'lookaddons');
			}
		}
		
		$profile_znote_data = user_znote_character_data($user_id, 'created', 'hide_char', 'comment');
		
		$guild_exist = false;
		
		if (get_character_guild_rank($user_id) > 0) 
		{
			$guild_exist = true;
			$guild = get_player_guild_data($user_id);
			$guild_name = get_guild_name($guild['guild_id']);
		}
		
		?>
		
		<!-- PROFILE MARKUP HERE-->
		
		<!-- Profile name -->
		<h1><?php if ($loadOutfits): ?><img src="<?php echo $config['show_outfits']['imageServer']; ?>?id=<?php echo $profile_data['looktype']; ?>&addons=<?php echo $profile_data['lookaddons']; ?>&head=<?php echo $profile_data['lookhead']; ?>&body=<?php echo $profile_data['lookbody']; ?>&legs=<?php echo $profile_data['looklegs']; ?>&feet=<?php echo $profile_data['lookfeet']; ?>" alt="img"><?php endif; ?><font class="profile_font" name="profile_font_header"><?php echo $profile_data['name']; ?></font></h1>
			<ul class="unstyled">
				<?php
				$flags = $config['country_flags'];
				if ($flags['enabled'] && $flags['characterprofile']) { 
					$account_data = user_znote_account_data($profile_data['account_id'], 'flag');

					if (strlen($account_data['flag']) > 0):
						?><!-- Player country data -->
						<li><font class="profile_font" name="profile_font_country">Country: <?php echo '<img src="' . $flags['server'] . '/' . $account_data['flag'] . '.png">'; ?></font></li>
						<?php
					endif;
				}
				?>
				
				<!-- Player Position -->
				<?php if ($profile_data['group_id'] > 1) { ?>
				<li><font class="profile_font" name="profile_font_position">Position: <?php echo group_id_to_name($profile_data['group_id']); ?></font></li>
				<?php } ?>

				<!-- Player male / female -->
				<li>
					<font class="profile_font" name="profile_font_level">Sex: 
						<?php 
						if ($profile_data['sex'] == 1) 
						{
							echo 'Male';
						} 
						else 
						{
							echo 'Female';
						}
						?>
					</font>
				</li>
				
				<!-- Player level -->
				<li>
					<font class="profile_font" name="profile_font_level">Level: 
						<?php 
						echo $profile_data['level']; 
						?>
					</font>
				</li>
				
				<!-- Player vocation -->
				<li><font class="profile_font" name="profile_font_vocation">Vocation: <?php echo vocation_id_to_name($profile_data['vocation']); ?></font></li>
				
				<!-- Player guild -->
				<?php 
				if ($guild_exist) 
				{
				?>
				<li>
					<font class="profile_font" name="profile_font_vocation">
						<b><?php echo $guild['rank_name']; ?> </b> of <a href="guilds.php?name=<?php echo $guild_name; ?>"><?php echo $guild_name; ?></a>
					</font>
				</li>
				<?php
				}
				?>
				<!-- Player last login -->
				<li>
					<font class="profile_font" name="profile_font_lastlogin">Last Login: 
					<?php
					if ($profile_data['lastlogin'] != 0) 
					{
						echo getClock($profile_data['lastlogin'], true, true);
					} 
					else 
					{
						echo 'Never.';
					}
					?>
					</font>
				</li>
				
				<!-- Achievement start -->
				<?php 
				if ($config['Ach']) 
				{ 
					foreach ($achievementPoints as $achievement) 
					{
						//if player doesn't have any achievement points it won't echo the line below.
						if ($achievement > 0)
						{
							echo '<li>Achievement Points: ' . $achievement . '</li>'; 
						}
					}
				}
				?>
				<!-- Achievement end -->
				
				<!-- Display house start -->
				<?php
				if ($config['TFSVersion'] !== 'TFS_02') 
				{
					$townid = ($config['TFSVersion'] === 'TFS_03') ? 'town' : 'town_id';
					$houses = mysql_select_multi("SELECT `id`, `owner`, `name`, `$townid` AS `town_id` FROM `houses` WHERE `owner` = $user_id;");
					
					if ($houses) 
					{
						$playerlist = array();
						foreach ($houses as $h) 
						{
							if ($h['owner'] > 0)
							{
								$playerlist[] = $h['owner'];
							}
								
							if ($profile_data['id'] = $h['owner']) 
							{
							?>
								<li>House: <?php echo $h['name']; ?>, <?php 
									foreach ($config['towns'] as $key => $value) 
									{
										if ($key == $h['town_id']) 
										{
											echo $value;
										}
									}
							 ?>
								</li>
							<?php
							}
						}
					}
				}
				?>
				<!-- Display house end -->
				
				<!-- Display player status -->
				<li><font class="profile_font" name="profile_font_status">Status:</font> <?php
				if ($config['TFSVersion'] == 'TFS_10') 
				{
					if ($profile_data['online']) 
					{
						echo '<font class="profile_font" name="profile_font_online" color="green"><b>ONLINE</b></font>';
					} 
					else 
					{
						echo '<font class="profile_font" name="profile_font_online" color="red"><b>OFFLINE</b></font>';
					}
				} 
				else 
				{
					if ($profile_data['online']) 
					{
						echo '<font class="profile_font" name="profile_font_online" color="green"><b>ONLINE</b></font>';
					} 
					else 
					{
						echo '<font class="profile_font" name="profile_font_online" color="red"><b>OFFLINE</b></font>';
					}
				}
				?>
				</li>
				<!-- Display player status end -->
				
				<!-- Player created -->
				<li><font class="profile_font" name="profile_font_created">Created: <?php echo getClock($profile_znote_data['created'], true); ?></font></li>
				
				<!-- Player Comment -->
				<?php
				//if player doesnt have set a comment dont show it.
				if (!empty($profile_znote_data['comment']))
				{ ?>
					<li>
						<font class="profile_font" name="profile_font_comment">Comment:</font><br>
						<textarea name="profile_comment_textarea" cols="70" rows="10" readonly="readonly" class="span12"><?php echo preg_replace('/\v+|\\\r\\\n/','<br/>',$profile_znote_data['comment']); ?></textarea>
					</li>
				<?php
				}
				?>
				
				<!-- Achievements start -->
				<?php if ($config['Ach']) 
				{ ?>			
					<h3 class="header-ok">Achievements</h3>
					<div id="accordion">
						<h3>Show/hide player achievements</h3>
						<div>
							<table class="table table-striped table-bordered">
								<tbody>
									<style>
										#secondD {
											margin-left:0px;
										}
									</style>
									<?php
									foreach ($config['achievements'] as $key => $achiv) 
									{
										$uery = mysql_select_single("SELECT `player_id`, `value`, `key` FROM `player_storage` WHERE `player_id`='$user_id' AND `key`='$key' LIMIT 1;");
										if (!empty($uery) || $uery !== false) 
										{
											foreach ($uery as $luery) 
											{
												if ($luery == $key) 
												{
													if (!array_key_exists($key, $achiv)) 
													{
														echo '<tr><td width="17%">' .$achiv[0]. '</td><td>' .$achiv[1]. '</td>';
														
														if (!isset($achiv['secret'])) 
														{
															echo '<td><img id="secondD" src="http://img04.imgland.net/PuMz0mVqSG.gif"></td>';
														}
														
														echo '<td>'. $achiv['points'] .'</td>';
														echo '<tr>';
													}
												}
											}
										}
									}
									?>
								</tbody>
							</table>
						</div>
					</div><br>
				<?php
				} 
				?>
				<!-- Achievements end -->
				
				<!-- DEATH LIST -->
				<li>
					<b>Death List:</b><br>
					<?php
					if ($config['TFSVersion'] == 'TFS_02') 
					{
						$array = user_fetch_deathlist($user_id);
						if ($array) 
						{
						?>
							<ul>
							<?php
							// Design and present the list
							foreach ($array as $value) 
							{ ?>
								<li>
								<?php
								$value['time'] = getClock($value['time'], true);
								
								if ($value['is_player'] == 1) 
								{
									$value['killed_by'] = 'player: <a href="characterprofile.php?name='. $value['killed_by'] .'">'. $value['killed_by'] .'</a>';
								} 
								else 
								{
									$value['killed_by'] = 'monster: '. $value['killed_by'] .'.';
								}
								
								echo '['. $value['time'] .'] Killed at level '. $value['level'] .' by '. $value['killed_by']; ?>
								</li>
							<?php
							}
							?>
							</ul>
							<?php
						} 
						else 
						{
							echo '<b><font color="green">This player has never died.</font></b>';
						}
					} 
					else if ($config['TFSVersion'] == 'TFS_10') 
					{
						$deaths = mysql_select_multi("SELECT 
							`player_id`, `time`, `level`, `killed_by`, `is_player`, 
							`mostdamage_by`, `mostdamage_is_player`, `unjustified`, `mostdamage_unjustified` 
							FROM `player_deaths` 
							WHERE `player_id`=$user_id ORDER BY `time` DESC LIMIT 10;");

						if ($deaths)
						{ 
							foreach ($deaths as $d) 
							{
								?>
								<li>
									<?php echo "<b>".getClock($d['time'], true, true)."</b>";
									$lasthit = ($d['is_player']) ? "<a href='characterprofile.php?name=".$d['killed_by']."'>".$d['killed_by']."</a>" : $d['killed_by'];
									echo ": Killed at level ".$d['level']." by $lasthit";
									if ($d['unjustified']) 
									{echo " <font color='red' style='font-style: italic;'>(unjustified)</font>";}
								
									$mostdmg = ($d['mostdamage_by'] !== $d['killed_by']) ? true : false;
									
									if ($mostdmg) 
									{
										$mostdmg = ($d['mostdamage_is_player']) ? "<a href='characterprofile.php?name=".$d['mostdamage_by']."'>".$d['mostdamage_by']."</a>" : $d['mostdamage_by'];
										echo "<br>and by $mostdmg.";
										
										if ($d['mostdamage_unjustified']) 
										{ echo " <font color='red' style='font-style: italic;'>(unjustified)</font>"; }
									} 
									else 
									{ echo " <b>(soloed)</b>"; }
									?>
								</li>
								<?php
							}
						}
						else 
						{
							echo '<b><font color="green">This player has never died.</font></b>'; 
						}
					} 
					else if ($config['TFSVersion'] == 'TFS_03') 
					{
						//mysql_select_single("SELECT * FROM players WHERE name='TEST DEBUG';");
						$array = user_fetch_deathlist03($user_id);
						
						if ($array) 
						{?>
							<ul>
								<?php
								// Design and present the list
								foreach ($array as $value) 
								{ ?>
									<li>
									<?php
									$value[3] = user_get_killer_id(user_get_kid($value['id']));
									
									if ($value[3] !== false && $value[3] >= 1) 
									{
										$namedata = user_character_data((int)$value[3], 'name');
										
										if ($namedata !== false) 
										{
											$value[3] = $namedata['name'];
											$value[3] = 'player: <a href="characterprofile.php?name='. $value[3] .'">'. $value[3] .'</a>';
										} 
										else 
										{
											$value[3] = 'deleted player.';
										}
									} 
									else 
									{
										$value[3] = user_get_killer_m_name(user_get_kid($value['id']));
										
										if ($value[3] === false) 
										{ $value[3] = 'deleted player.'; }
									}
									
									echo '['. getClock($value['date'], true) .'] Killed at level '. $value['level'] .' by '. $value[3];
									echo '</li>';
								}
								?>
							</ul>
							<?php
						} 
						else { echo '<b><font color="green">This player has never died.</font></b>'; }
					}
					?>
				</li>
				<!-- END DEATH LIST -->
				
				<!-- QUEST PROGRESSION -->
<li>	<b>Quest status:</b><br>			
<table id="questTable">
	<?php
	$completed = '<font color="green">[Completed]</font>';
	$notstarted = '<font color="red">[Not started]</font>';;
	function Progress($min, $max, $design = '<font color="orange">[x%]</font>') {
		$design = explode("x%",$design);
		$percent = ($min / $max) * 100;
		return $design[0] . $percent . $design[1];
	}
	$quests = array(


		// Simple quests
		'Boulder Badge' => 92000,
		'Cascade Badge' => 92001,
		'Thunder Badge' => 92002,
		'Rainbow Badge' => 92003,
		'Soul Badge' => 92004,
		'Marsh Badge' => 92005,
		'Volcano Badge' => 92006,
		'Earth Badge' => 92007,
		'Zephyr Badge' => 92008,
		'Hive Badge' => 92009,
		'Plain Badge' => 92010,
		'Fog Badge' => 92011,
		'Storm Badge' => 92012,
		'Mineral Badge' => 92013,
		'Glacier Badge' => 92014,
		'Rising Badge' => 92015,

		// Advanced quest with progress par:
		'Catches diarios' => array(
			91000,
			3,
		),

		'Lugia de Vermilion' => 10200,
		'Moltres de Cerulean' => 10201,
		'Articuno de Snow' => 10202,
		'Zapdos de Nostrus' => 10203,
		'Poison Stone Lavender' => 10204,
		'Punch Stone Nostrus' => 10205,
		'Fight Tower' => 10206,
		'Darkness Stone Cerulean' => 10207,
		'Rock Stone Cerulean' => 10210,
		'Enigma Stone Fuchsia' => 10211,
		'Rock Stone Pewter' => 10212,
		'Thunder Shiny Nostrus' => 10213,
		'Shining Box Nostrus' => 10215,
		'Fire Quest Cinnabar' => 10216,
		'Subterranea Saffron' => 10217,
		'Earth Stone Cerulean' => 10218,
		'Fire Stone Cinnabar' => 10219,
		'Rock Stone Saffron' => 10220,
		'Annihilator Fuchsia' => 10221,
		'Outland Darkness Stone' => 10224,
		'Enigma Stone Nostrus' => 10225,
		'Shiny Moltres Cinnabar' => 10226,
		'Outland Leaf' => 10227,
		'Outland Celebi' => 10228,
		'Outland Aquatica' => 10229,
		'Outland Psiquica' => 10230,
		'Outland Steel' => 10231,
		'Outland Fire' => 10232,
		'Outland Regirock' => 10233,
		'Outland Fight' => 10234,
		'Outland Poison' => 10235,
		'Outland Articuno' => 10236,
		'Outland Heart' => 10237,
		'Outland Nature' => 10238,
		'Outland Ice' => 10239,
		'Outland Zapdos' => 10240,
		'Monte da Lua' => 10241,
		'Cemiterio de Lavender' => 10242,
		'Outland Shiny Celebi' => 10243,
		'Mew Dungeon' => 10244,
		'Cerulean Cave' => 10245,
		'Indigo League' => 91007,

		'Oak Request' => 92016,
		'The Pokemaster' => 92017,
		'Red Request' => 92018,
		"Catch'em All" => 92019,
		'Elder Man Request' => 92020,

	);
	?>
	<tr class="yellow">
		<td>Quest Name</td>
		<td>Status</td>
	</tr>
	<?php
	// Rolling through quests
	foreach ($quests as $key => $quest) {

		// Is quest NOT an array (advanced quest?)
		if (!is_array($quest)) {
			// Query to find quest results
			$query = mysql_select_single("SELECT `value` FROM `player_storage` WHERE `key`='$quest' AND `player_id`='$user_id' AND `value`='1' LIMIT 1;");

			if ($query !== false) $quest = $completed;
			else $quest = $notstarted;

		} else {
			$query = mysql_select_single("SELECT `value` FROM `player_storage` WHERE `key`='".$quest[0]."' AND `player_id`='$user_id' AND `value`>'0' LIMIT 1;");
			if (!$query) $quest = $notstarted;
			else {
				if ($query['value'] >= $quest[1]) $quest = $completed;
				else $quest = Progress($query['value'], $quest[1]);
			}
		}
		?>
		<tr>
			<td><?php echo $key; ?></td>
			<td><?php echo $quest; ?></td>
		</tr>
		<?php
	}
	?>
</table>
</li>	
				<!-- END QUEST PROGRESSION -->
				
				<!-- CHARACTER LIST -->
				<?php
				if (user_character_hide($profile_data['name']) != 1 && user_character_list_count(user_character_account_id($name)) > 1) 
				{
				?>
					<li>
						<b>Other visible characters on this account:</b><br>
						<?php
						$characters = user_character_list(user_character_account_id($profile_data['name']));
						// characters: [0] = name, [1] = level, [2] = vocation, [3] = town_id, [4] = lastlogin, [5] = online
						if ($characters && count($characters) > 0) 
						{
							?>
							<table id="characterprofileTable" class="table table-striped table-hover">
								<tr class="yellow">
									<th>Name:</th>
									<th>Level:</th>
									<th>Vocation:</th>
									<th>Last login:</th>
									<th>Status:</th>
								</tr>
								
								<?php
								// Design and present the list
								foreach ($characters as $char) 
								{
									if ($char['name'] != $profile_data['name']) 
									{
										if (hide_char_to_name(user_character_hide($char['name'])) != 'hidden') 
										{ ?>
											<tr>
												<td><a href="characterprofile.php?name=<?php echo $char['name']; ?>"><?php echo $char['name']; ?></a></td>
												<td><?php echo (int)$char['level']; ?></td>
												<td><?php echo $char['vocation']; ?></td>
												<td><?php echo $char['lastlogin']; ?></td>
												<td><?php echo $char['online']; ?></td>
											</tr>
										<?php
										}
									}
								}
							?>
							</table>
							<?php
						} 
						else 
						{
							echo '<b><font color="green">This player has never died.</font></b>';
						}
						?>
					</li>
				<?php
				}
				?>
				<!-- END CHARACTER LIST -->
				
				<!--<li>
					<font class="profile_font" name="profile_font_share_url">Address: 
					<a href="
						<?php 
						if ($config['htwrite']) 
						{ 
							echo "http://" . $_SERVER['HTTP_HOST']."/" . $profile_data['name']; 
						}
						else 
						{ 
							echo "http://" . $_SERVER['HTTP_HOST'] . "/characterprofile.php?name=" . $profile_data['name']; 
						}	
						?>">
						<?php
						if ($config['htwrite']) 
						{ 
							echo "http://".$_SERVER['HTTP_HOST']."/". $profile_data['name']; 
						}
						else 
						{ 
							echo "http://".$_SERVER['HTTP_HOST']."/characterprofile.php?name=". $profile_data['name']; 
						}
						?>
				</a></font></li>-->
			</ul>
		<!-- END PROFILE MARKUP HERE-->
		
		<?php
	} 
	else 
	{
		echo htmlentities(strip_tags($name, ENT_QUOTES)) . ' does not exist.';
	}
} 
else 
{
	header('Location: index.php');
}

include 'layout/overall/footer.php'; ?>
