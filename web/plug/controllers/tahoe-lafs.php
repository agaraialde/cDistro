<?php
//tahoe-lafs.php

$CONFIGS_DIR="/usr/lib/cDistro/tahoe-lafs-manager/";
$TAHOELAFS_CONF="tahoe-lafs.conf.default";

function index_get(){

	global $CONFIGS_DIR;	
	global $TAHOELAFS_CONF;
	$tahoeVariables = load_conffile($CONFIGS_DIR.$TAHOELAFS_CONF);

	$page = "";
	$buttons = "";

	$page .= hlc($tahoeVariables['PACKAGE_FULLNAME']);
	$page .= hl(t("A cloud storage system that distributes your data across multiple servers."),4);
	$page .= par(t("Tahoe-LAFS is a free and open cloud storage system. It distributes your data across multiple servers. Even if some of the servers fail or are taken over by an attacker, the entire filesystem continues to function correctly, preserving your privacy and security."));
		
	if ( ! isPackageInstall($tahoeVariables['PACKAGE_NAME']) ) {
		$page .= "<div class='alert alert-error text-center'>".t("Tahoe-LAFS is not installed on this machine")."</div>\n";
		$page .= par(t("To deploy a storage grid with Tahoe-LAFS you need one <strong>introducer</strong> and multiple <strong>nodes</strong> distributed by the network. Click on the button to install Tahoe-LAFS and start creating a storage grid or to join an existing one."));
		$buttons .= addButton(array('label'=>t("Install Tahoe-LAFS"),'class'=>'btn btn-success', 'href'=>'tahoe-lafs/install'));
	
		$page .= $buttons;	
		return(array('type' => 'render','page' => $page));
	}
	
	if( ! (introducerCreated($tahoeVariables['DAEMON_HOMEDIR']) || nodeCreated($tahoeVariables['DAEMON_HOMEDIR'])) ) {
		$page .= "<div class='alert alert-warning text-center'>".t("Tahoe-LAFS is installed on this machine but has not been configured yet")."</div>\n";
		$page .= par(t("To deploy a storage grid with Tahoe-LAFS you need one <strong>introducer</strong> and multiple <strong>nodes</strong> distributed by the network. Click on the buttons below to set up an introducer and start creating a storage grid or to add a storage node to an existing grid."));
		$buttons .= addButton(array('label'=>t("Create an introducer"),'class'=>'btn btn-success', 'href'=>'tahoe-lafs/introducer'));
		$buttons .= addButton(array('label'=>t("Create a storage node"),'class'=>'btn btn-success', 'href'=>'tahoe-lafs/node'));
		$buttons .= addButton(array('label'=>t("Uninstall Tahoe-LAFS"),'class'=>'btn btn-danger', 'href'=>'tahoe-lafs/purge'));
		
		$page .= $buttons;	
		return(array('type' => 'render','page' => $page));	
	}

	if ( introducerCreated($tahoeVariables['DAEMON_HOMEDIR']) ) {
		if ( introducerStarted($tahoeVariables['DAEMON_HOMEDIR'],$tahoeVariables['TAHOE_PID_FILE']) )
			$page .= "<div class='alert alert-success text-center'>".t("Tahoe-LAFS introducer running")."</div>\n";
		else
			$page .= "<div class='alert alert-warning text-center'>".t("Tahoe-LAFS introducer stopped")."</div>\n";
		
		$buttons .= addButton(array('label'=>t("Manage introducer"),'class'=>'btn btn-primary', 'href'=>'tahoe-lafs/introducer'));
	}
	if ( nodeCreated($tahoeVariables['DAEMON_HOMEDIR']) ) {
		if ( nodeStarted($tahoeVariables['DAEMON_HOMEDIR'],$tahoeVariables['TAHOE_PID_FILE']) )
			$page .= "<div class='alert alert-success text-center'>".t("Tahoe-LAFS node running")."</div>\n";
		else
			$page .= "<div class='alert alert-warning text-center'>".t("Tahoe-LAFS node stopped")."</div>\n";
		
		$buttons .= addButton(array('label'=>t("Manage node"),'class'=>'btn btn-primary', 'href'=>'tahoe-lafs/node'));	
	}
	
	$page .= $buttons;	
	return(array('type' => 'render','page' => $page));
}

function install(){
	global $CONFIGS_DIR;	
	global $TAHOELAFS_CONF;
	$tahoeVariables = load_conffile($CONFIGS_DIR.$TAHOELAFS_CONF);

	$page = "";
	$buttons = "";
	
	$page .= hlc("Tahoe-LAFS");
	$page .= hl(t("Installation"),4);

	if (isPackageInstall("tahoe-lafs")) {
 		$page .= "<div class='alert alert-success text-center'>".t("Tahoe-LAFS is already installed")."</div>\n";
		$page .= txt(t("Tahoe-LAFS installation information:"));
		$page .= ptxt(packageInstallationInfo("tahoe-lafs"));
 		$buttons .= addButton(array('label'=>t("Back to Tahoe-LAFS"),'class'=>'btn btn-default', 'href'=>'../tahoe-lafs'));
 		
 		$page .= $buttons;	
		return(array('type' => 'render','page' => $page));
	}
		
 	$pkgInstall = ptxt(installPackage("tahoe-lafs"));
	
	if (isPackageInstall("tahoe-lafs")) {
		$page .= "<div class='alert alert-success text-center'>".t("Tahoe-LAFS has been successfully installed")."</div>\n";
		$page .= txt(t("Installation process result:"));
		$page .= $pkgInstall;
			
		$postInstall = array();
		$postInstallAll = "";
			
		$page .= txt(t("Post-installation process:"));
		foreach (execute_program( 'addgroup --system tahoe' )['output'] as $key => $value) { $postInstall[] = $value; }
		foreach (execute_program( 'adduser --system --ingroup tahoe --home /var/lib/tahoe-lafs --shell /sbin/nologin tahoe' )['output'] as $key => $value) { $postInstall[] = $value; }
		foreach (execute_program( 'chown -vR tahoe:tahoe /var/lib/tahoe-lafs' )['output'] as $key => $value) { $postInstall[] = $value;}
		$postInstall[] = execute_program( 'cp -fv /usr/lib/cDistro/tahoe-lafs-manager/tahoe-lafs.init.d /etc/init.d/tahoe-lafs' )['output'][0];
		$postInstall[] = execute_program( 'cp -fv /usr/lib/cDistro/tahoe-lafs-manager/tahoe-lafs.etc.default /etc/default/tahoe-lafs' )['output'][0];
		foreach (execute_program( 'chmod -v +x /etc/init.d/tahoe-lafs' )['output'] as $key => $value) { $postInstall[] = $value; }
		$postInstall[] = execute_program( 'update-rc.d tahoe-lafs defaults' )['output'][0];

		foreach ($postInstall as $k => $v) { $postInstallAll .= $v.'<br/>'; }
		$page .= ptxt($postInstallAll);
		$buttons .= addButton(array('label'=>t("Back to Tahoe-LAFS"),'class'=>'btn btn-default', 'href'=>'../tahoe-lafs'));
		
		$page .= $buttons;	
		return(array('type' => 'render','page' => $page));
		}
			
	$page .= "<div class='alert alert-error text-center'>".t("Tahoe-LAFS installation failed")."</div>\n";
	$page .= txt(t("Installation process result:"));
	$page .= $pkgInstall;
	$buttons .= addButton(array('label'=>t("Back to Tahoe-LAFS"),'class'=>'btn btn-default', 'href'=>'install'));
	$buttons .= addButton(array('label'=>t("Retry installation"),'class'=>'btn btn-warning', 'href'=>'install'));
	
	$page .= $buttons;
	return(array('type' => 'render','page' => $page));
}

function purge(){
	global $CONFIGS_DIR;	
	global $TAHOELAFS_CONF;
	$tahoeVariables = load_conffile($CONFIGS_DIR.$TAHOELAFS_CONF);

	$page = "";
	$buttons = "";
	
	$page .= hlc("Tahoe-LAFS");
	$page .= hl(t("Uninstallation"),4);

	if ( ! isPackageInstall("tahoe-lafs" ) ) {
			$page .= "<div class='alert alert-warning text-center'>".t("Tahoe-LAFS is currently uninstalled")."</div>\n";
 			$buttons .= addButton(array('label'=>t("Back to Tahoe-LAFS"),'class'=>'btn btn-default', 'href'=>'../tahoe-lafs'));
 			
 			$page .= $buttons;	
			return(array('type' => 'render','page' => $page));
	}
 	
 	if ( introducerCreated($tahoeVariables['DAEMON_HOMEDIR']) || nodeCreated($tahoeVariables['DAEMON_HOMEDIR'])) {
		if ( introducerCreated($tahoeVariables['DAEMON_HOMEDIR']) ){
		$page .= "<div class='alert alert-warning text-center'>".t("A Tahoe-LAFS introducer is currently configured. Stop it and remove it before uninstalling Tahoe-LAFS.")."</div>\n";
		$buttons .= addButton(array('label'=>t("Manage Tahoe-LAFS introducer"),'class'=>'btn btn-primary', 'href'=>'introducer'));
		}
		if ( nodeCreated($tahoeVariables['DAEMON_HOMEDIR']) ){
		$page .= "<div class='alert alert-warning text-center'>".t("A Tahoe-LAFS node is currently configured. Stop it and remove it before uninstalling Tahoe-LAFS.")."</div>\n";
		$buttons .= addButton(array('label'=>t("Manage Tahoe-LAFS node"),'class'=>'btn btn-primary', 'href'=>'node'));
		}
		
		$page .= $buttons;
		return(array('type' => 'render','page' => $page));
	}
	
	$pkgUninstall = ptxt(uninstallPackage("tahoe-lafs"));
	
	if (isPackageInstall("tahoe-lafs")) {
		$page .= "<div class='alert alert-error text-center'>".t("Tahoe-LAFS uninstallation failed")."</div>\n";
		$page .= txt(t("Uninstallation process result:"));
		$page .= $pkgUninstall;
		$buttons .= addButton(array('label'=>t("Back to Tahoe-LAFS"),'class'=>'btn btn-default', 'href'=>'install'));
		$buttons .= addButton(array('label'=>t("Retry uninstallation"),'class'=>'btn btn-warning', 'href'=>'purge'));
		
		$page .= $buttons;
		return(array('type' => 'render','page' => $page));
	}
	
	$page .= "<div class='alert alert-success text-center'>".t("Tahoe-LAFS has been successfully uninstalled")."</div>\n";
	$page .= txt(t("Uninstallation process result:"));
	$page .= $pkgUninstall;
	
	$page .= txt(t("Post-uninstallation process:"));			
			
	$postUninstall = array();
	$postUninstallAll = "";
	
	foreach (execute_program( 'update-rc.d tahoe-lafs remove' )['output'] as $key => $value) { $postUninstall[] = $value.'<br/>'; }	
	$postUninstall[] = execute_program_shell( 'rm -fv /etc/init.d/tahoe-lafs' )['output'];
	$postUninstall[] = execute_program_shell( 'rm -fv /etc/default/tahoe-lafs' )['output'];
	foreach (execute_program( 'deluser --system --remove-home tahoe' )['output'] as $key => $value) { $postUninstall[] = $value.'<br/>'; }
	foreach (execute_program( 'delgroup --system tahoe' )['output'] as $key => $value) { $postUninstall[] = $value.'<br/>'; }
	$postUninstall[] = execute_program_shell( 'rm -rvf /var/lib/tahoe-lafs' )['output'] ;

	foreach ($postUninstall as $v) { $postUninstallAll .= $v; }
	$page .= ptxt($postUninstallAll);
	
	$buttons .= addButton(array('label'=>t("Back to Tahoe-LAFS"),'class'=>'btn btn-default', 'href'=>'../tahoe-lafs'));
	
	$page .= $buttons;
	return(array('type' => 'render','page' => $page));
}


function introducer(){
	global $CONFIGS_DIR;	
	global $TAHOELAFS_CONF;
	$tahoeVariables = load_conffile($CONFIGS_DIR.$TAHOELAFS_CONF);
	
	$page = "";
	$buttons = "";
	
	$page .= hlc("Tahoe-LAFS");
	$page .= hl(t("Introducer"),4);

	if ( ! isPackageInstall("tahoe-lafs" ) ) {
			$page .= "<div class='alert alert-warning text-center'>".t("Tahoe-LAFS is currently uninstalled")."</div>\n";
 			$buttons .= addButton(array('label'=>t("Back to Tahoe-LAFS"),'class'=>'btn btn-default', 'href'=>'../tahoe-lafs'));
 			$buttons .= addButton(array('label'=>t("Install Tahoe-LAFS"),'class'=>'btn btn-success', 'href'=>'../tahoe-lafs'));
 			
			$page .= $buttons; 			
 			return(array('type' => 'render','page' => $page));
	}
	
 	if ( ! introducerCreated($tahoeVariables['DAEMON_HOMEDIR'] )) {
		$page .= "<div class='alert alert-warning text-center'>".t("Tahoe-LAFS introducer is currently not configured")."</div>\n";
		$page .= par(t("To deploy a storage grid with Tahoe-LAFS you need one <strong>introducer</strong> and multiple <strong>storage nodes</strong> distributed by the network. Use this page to set up an introducer on this machine. After that, storage nodes will be able to join your introducer to deploy the storage grid."));
		$page .= createForm(array('class'=>'form-horizontal'));
		$page .= addInput('INTRODUCER_NICKNAME',t('Nickname'),'MyGrid-MyIntroducer','',t("A short nickname to identify the introducer in the storage grid."));
		$page .= addInput('INTRODUCER_NICKNAME',t('Nickname'),'MyGrid-MyIntroducer','',t("A short nickname to identify the introducer in the storage grid."));
		$page .= addInput('INTRODUCER_WEBPORT',t('Web port'),8228,'',t("The port where the introducer's web management interface will run."));
		$page .= addFixedInput('INTRODUCER_DIR',t('Folder'),$tahoeVariables['DAEMON_HOMEDIR'].'/introducer','',t("The installation path for the introducer."));
		$page .= addInput('INTRODUCER_PUBLIC',t('Public'),true,'',t("Check this box to make this introducer public and announce to the network via Avahi."));
		$buttons .= addSubmit(array('label'=>t('Create introducer'),'class'=>'btn btn-success'));
		
		$page .= $buttons; 			
 		return(array('type' => 'render','page' => $page));
	}
			
	if ( introducerStarted($tahoeVariables['DAEMON_HOMEDIR'],$tahoeVariables['TAHOE_PID_FILE'])) {
		$page .= "<div class='alert alert-success text-center'>".t("Tahoe-LAFS introducer is running")."</div>\n";

		$buttons .= addButton(array('label'=>t("Back to Tahoe-LAFS"),'class'=>'btn btn-default', 'href'=>'../tahoe-lafs'));
		$buttons .= addButton(array('label'=>t("Stop Tahoe-LAFS introducer"),'class'=>'btn btn-danger', 'href'=>'stopIntroducer'));
		$page .= ptxt(execute_program("sed 's/,127\.0\.0\.1:.*\//\//' /var/lib/tahoe-lafs/introducer/introducer.furl")['output'][0]);
		
		$page .= $buttons; 			
 		return(array('type' => 'render','page' => $page));
	} 	
	
	else {
		$page .= "<div class='alert alert-error text-center'>".t("Tahoe-LAFS introducer is stopped")."</div>\n";
		$buttons .= addButton(array('label'=>t("Back to Tahoe-LAFS"),'class'=>'btn btn-default', 'href'=>'../tahoe-lafs'));
		$buttons .= addButton(array('label'=>t("Start Tahoe-LAFS introducer"),'class'=>'btn btn-success', 'href'=>'startIntroducer'));
		
		$page .= $buttons; 			
 			return(array('type' => 'render','page' => $page));
	}
}
		



function introducer_post(){
	global $CONFIGS_DIR;	
	global $TAHOELAFS_CONF;
	global $staticFile;
	$tahoeVariables = load_conffile($CONFIGS_DIR.$TAHOELAFS_CONF);

	$page = "";
	
	$page .= hlc("Tahoe-LAFS");
	$page .= hl(t("Introducer creation"),4);


	if ( ! isPackageInstall("tahoe-lafs" ) ) {
			$page .= "<div class='alert alert-warning text-center'>".t("Tahoe-LAFS is currently uninstalled")."</div>\n";
 			$buttons .= addButton(array('label'=>t("Back to Tahoe-LAFS"),'class'=>'btn btn-default', 'href'=>'../tahoe-lafs'));
 			$buttons .= addButton(array('label'=>t("Install Tahoe-LAFS"),'class'=>'btn btn-success', 'href'=>'../tahoe-lafs'));
 			
			$page .= $buttons; 			
 			return(array('type' => 'render','page' => $page));
	}
 	
 	if ( introducerCreated($tahoeVariables['DAEMON_HOMEDIR'] )) {
 		setFlash(t("Tahoe-LAFS introducer is already created"));
		return(array('type'=>'redirect','url'=> $staticFile.'/tahoe-lafs'));
 	}
	
	$postCreate = array();
	foreach (execute_program( '/usr/bin/tahoe create-introducer /var/lib/tahoe-lafs/introducer')['output'] as $k => $v) { $postCreate[] = $v; }
	execute_program_shell( 'sed -i "s/^nickname.*$/nickname = '.$_POST['INTRODUCER_NICKNAME'].'/" /var/lib/tahoe-lafs/introducer/tahoe.cfg' );
	execute_program_shell( 'sed -i "s/^web\.port.*$/web\.port = '.$_POST['INTRODUCER_WEBPORT'].'/" /var/lib/tahoe-lafs/introducer/tahoe.cfg' );
	
	if( execute_shell( "grep -q '^AUTOSTART' /etc/default/tahoe-lafs" )['return'] == 0 ) {
		execute_program_shell( 'sed -i "s/\" /\"/" /etc/default/tahoe-lafs' );
		execute_program_shell( 'sed -i "s/\" /\"/" /etc/default/tahoe-lafs' );
		execute_program_shell( 'sed -i "s/^AUTOSTART=\"[^\"]*/& introducer /" /etc/default/tahoe-lafs' );
		execute_program_shell( 'sed -i "s/ \"/\"/" /etc/default/tahoe-lafs' );
		execute_program_shell( 'sed -i "s/ \"/\"/" /etc/default/tahoe-lafs' );
	}
	else
		execute_program_shell( 'echo "AUTOSTART=introducer" >> /etc/default/tahoe-lafs' );			
	
			
	foreach (execute_program( 'chown -vR tahoe:tahoe /var/lib/tahoe-lafs/introducer' )['output'] as $key => $value) { $postCreate[] = $value; }
	$postCreateAll = "";
		foreach ($postCreate as $k => $v) { $postCreateAll .= $v.'<br/>'; }
		
	$page .= txt(t("Introducer creation process result:"));	
	if (introducerCreated($tahoeVariables['DAEMON_HOMEDIR'] ))
		$page .= "<div class='alert alert-success text-center'>".t("Tahoe-LAFS introducer successfully created")."</div>\n";
	else
		$page .= "<div class='alert alert-error text-center'>".t("Tahoe-LAFS introducer creation failed")."</div>\n";
	
	$page .= ptxt($postCreateAll);

	$postStart = array();
	foreach (execute_program( '/etc/init.d/tahoe-lafs restart')['output'] as $k => $v) { $postStart[] = $v; }
	
	//This pause is needed in order to let the server start before showing the success/error text
	sleep(5);
	
	$postStartAll = "";
		foreach ($postStart as $k => $v) { $postStartAll .= $v.'<br/>'; }

		
	$page .= txt(t("Restarting Tahoe-LAFS:"));
	$page .= ptxt($postStartAll);
	
	if ( introducerStarted($tahoeVariables['DAEMON_HOMEDIR'],$tahoeVariables['TAHOE_PID_FILE']) )
		$page .= "<div class='alert alert-success text-center'>".t("Tahoe-LAFS introducer successfully started")."</div>\n";
	else
		$page .= "<div class='alert alert-error text-center'>".t("Tahoe-LAFS introducer start failed")."</div>\n";
	
	 $page .= addButton(array('label'=>t("Back to Tahoe-LAFS"),'class'=>'btn btn-default', 'href'=>'../tahoe-lafs'));
 			
	return(array('type' => 'render','page' => $page));
}


function startIntroducer(){
	global $staticFile;
/*
	No se per què el server es pensa que la pàgina encarà no s'ha acabat de carregar. :-?
	Revisar, per la parada si que funciona.
	Potser l'script a de fer un fork que no depengui del pare.
*/
	execute_bg_shell('/etc/init.d/tahoe-lafs start introducer');
	$page = "";
	$page .= "<div class='alert alert-warning'>".t("Tahoe-LAFS introducer started.")." <a href='".$staticFile.'/'.'tahoe-lafs/introducer'."'>".t("Return to the previous page")."</a>.</div>";
	return(array('type'=>'render', 'page'=> $page));
	exit();
}

function stopIntroducer(){
	global $staticFile; 

	$r = execute_program('/etc/init.d/tahoe-lafs stop introducer');
	if ($r['return'] == 0) {
		setFlash(t('Tahoe-LAFS introducer stopped').'!');
	}

	return(array('type'=> 'redirect', 'url' => $staticFile.'/'.'tahoe-lafs/introducer'));	
}

function viewDevice(){
	global $Parameters,$staticFile;

	if (isset($Parameters) && isset($Parameters[0])){
		$r = execute_program_shell('ip addr show dev '.$Parameters[0]);		
		$page = "";
		$page .= "<div class='alert alert-warning'>";
		$page .= "<pre>";
		$page .= $r['output'];
		$page .= "</pre>";
		$page .= t("You can return to the previous")." <a href='".$staticFile.'/'.'getinconf'."'>page</a>.</div>";
		return(array('type'=>'render', 'page'=> $page));
	}
	return(array('type'=> 'redirect', 'url' => $staticFile.'/'.'getinconf'));		
}

function nodeCreated($dir){
	if (is_dir("$dir/node"))
		return 1;
	else	
		return 0;
}

function introducerCreated($dir){
	if (is_dir("$dir/introducer"))
		return 1;
	else	
		return 0;
}

function introducerStarted($dir,$pidfile){
	if (is_file("$dir/introducer/$pidfile"))
		return 1;
	else	
		return 0;
}

function nodeStarted($dir,$pidfile){
	if (is_file("$dir/node/$pidfile"))
		return 1;
	else	
		return 0;
}

function nothing(){

		$page = "";
		$page .= "<div class='alert alert-warning'>";
		$page .= t("Nothing to do.");
		$page .= "</div>";
		return(array('type'=>'render', 'page'=> $page));

}

?>