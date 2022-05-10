<?php

// TMS Rewritten - Early 2010
// By Joseph Robert Gillotti

defined('in_tms') or exit;
// Log us out
$ui->killSession();

// Go home
redirect($entry_point);
