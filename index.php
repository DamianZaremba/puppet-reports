<?php
	// Requirements
	global $config;
	require_once('configuration.php');
	require_once('lib/spyc/spyc.php');
	$errors = array();

	// Sanity checks
	if(!is_dir($config['report_dir'])) {
		$errors[] = "The specified report dir couldn't be accessed.";
	}

	// Load the servers
	$servers = array();
	if($handle = opendir($config['report_dir'])) {
		while (false !== ($entry = readdir($handle))) {
			if($entry == "." || $entry == "..")
				continue;

			if(is_dir($config['report_dir'] . "/" . $entry)) {
				$servers[] = $entry;
			}
		}
		closedir($handle);
	}
	asort($servers);
?>


<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<title>Puppet Reports</title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<meta name="description" content="">
		<meta name="author" content="">

		<!-- Le styles -->
		<link href="assets/css/bootstrap.css" rel="stylesheet">
		<style type="text/css">
			body {
				padding-top: 60px;
				padding-bottom: 40px;
			}
			.sidebar-nav {
				padding: 9px 0;
			}
		</style>
		<link href="assets/css/bootstrap-responsive.css" rel="stylesheet">

		<!--[if lt IE 9]>
			<script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
		<![endif]-->
	</head>
	<body>
		<div class="navbar navbar-fixed-top">
			<div class="navbar-inner">
				<div class="container-fluid">
					<a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
					</a>
					<a class="brand" href="#">Puppet reports</a>
					<div class="nav-collapse">
						<ul class="nav">
							<li><a href="index.php">Home</a></li>
						</ul>
					</div>
				</div>
			</div>
		</div>

		<div class="container">
			<?php
				if(count($errors) > 0) {
			?>
				<h1>Configuration error</h1>
				<hr />
				<?php
					foreach($errors as $error) {
				?>
				<p><?php echo $error; ?></p>
				<?php
					}
				?>
			<?php } else { ?>
				<?php
					// Report
					if(isset($_GET) && array_key_exists('server', $_GET) && !empty($_GET['server']) &&
						array_key_exists('report', $_GET) && !empty($_GET['report'])) {

						$server_path = str_replace('..', '', $_GET['server']);
						$server_path = str_replace('/', '', $server_path);
						$server_path = str_replace(' ', '', $server_path);
						$server_path = str_replace('//', '/', $config['report_dir'] . '/' . $server_path);

						$report_path = str_replace('..', '', $_GET['report']);
						$report_path = str_replace('/', '', $report_path);
						$report_path = str_replace(' ', '', $report_path);
						$report_path = str_replace('//', '/', $server_path . '/' . $report_path . '.yaml');
				?>
						<h1>Report <?php echo htmlentities($_GET['report']); ?> (<?php echo htmlentities($_GET['server']); ?>)</h1>
						<hr />
						<?php
							if(!is_file($report_path)) {
						?>
								<p>Report not found.</p>
						<?php
							} else {
								$data = spyc_load_file($report_path);
								$data = array_shift($data);
								if(isset($data)) {
						?>
									<h2>Overview</h2>
									<table>
										<tr>
											<td align="center"><b>Run time:</b></td>
											<td><?php echo htmlentities($data['time']); ?></td>
										</tr>
										<tr>
											<td align="center"><b>Host:</b></td>
											<td><?php echo htmlentities($data['host']); ?></td>
										</tr>
										<tr>
											<td align="center"><b>Run type:</b></td>
											<td><?php echo htmlentities($data['kind']); ?></td>
										</tr>
										<tr>
											<td align="center"><b>Status:</b></td>
											<td><?php echo htmlentities($data['status']); ?></td>
										</tr>
									</table>
									<hr />

									<h2>Logs</h2>
									<?php
										if(array_key_exists('logs', $data) && count($data['logs']) > 0) {
									?>
									<table width="90%">
										<tr>
											<td align="center"><b>Level</b></td>
											<td align="center"><b>Source</b></td>
											<td align="center"><b>Time</b></td>
											<td align="center"><b>Tags</b></td>
											<td align="center"><b>Message</b></td>
										</tr>
										<?php
											foreach($data['logs'] as $log) {
										?>
										<tr>
											<td><?php echo htmlentities($log['level']); ?></td>
											<td><?php echo htmlentities($log['source']); ?></td>
											<td><?php echo htmlentities($log['time']); ?></td>
											<td><?php echo htmlentities(implode(',', $log['tags'])); ?></td>
											<td width="40%"><pre><?php echo $log['message']; ?></pre></td>
										</tr>
										<?php } ?>
									</table>
									<?php
										} else {
											echo '<p>No logs found</p>';
										}
									?>
						<?php
								} else {
						?>
								<p>Report could not be loaded</p>
						<?php
								}
						?>
						<?php
							}
						?>
				<?php
					// Server view
					} elseif(isset($_GET) && array_key_exists('server', $_GET) && !empty($_GET['server'])) {
				?>
						<h1>Server (<?php echo htmlentities($_GET['server']); ?>)</h1>
						<hr />
						<?php
							$reports = array();
							$server_path = str_replace('..', '', $_GET['server']);
							$server_path = str_replace('/', '', $server_path);
							$server_path = str_replace(' ', '', $server_path);
							$server_path = str_replace('//', '/', $config['report_dir'] . '/' . $server_path);

							if($handle = opendir($server_path)) {
								while (false !== ($entry = readdir($handle))) {
									if($entry == "." || $entry == "..")
										continue;

									if(is_file($server_path . "/" . $entry)) {
										$reports[] = str_replace('.yaml', '', $entry);
									}
								}
								closedir($handle);
							}
							asort($reports);
						?>

						<?php
							if(count($reports) > 0) {
								echo '<table width="90%">';
								echo '<th>';
								echo '<td>ID</td>';
								echo '<td>Run Time</td>';
								echo '<td>Status</td>';
								echo '<td></td>';
								echo '</th>';

								foreach($reports as $report) {
									$data = spyc_load_file($server_path . '/' . $report . '.yaml');
									$data = array_shift($data);

									echo '<tr>';
									echo '<td>' . htmlentities($report) . '</td>';
									echo '<td>' . ((isset($data['time'])) ? htmlentities($data['time']) : '?') . '</td>';
									echo '<td>' . ((isset($data['status'])) ? htmlentities($data['status']) : '?') . '</td>';
									echo '<td><a href="index.php?server=' . urlencode($_GET['server']);
									echo '&report=' . $report . '">View</a></td>';
									echo '</tr>';
									unset($data);
								}
								echo '</table>';
							} else { ?>
								<p>No reports available.</p>
							<?php } ?>

				<?php
					// Home page
					} else {
				?>
						<h1>Servers</h1>
						<hr />
						<?php
							foreach($servers as $server) {
								echo '<p><a href="index.php?server=' . urlencode($server) . '">';
								echo htmlentities($server) . '</a></p>';
							}
						?>
				<?php
					}
				?>
			<?php } ?>
		</div>
	</body>
</html>
