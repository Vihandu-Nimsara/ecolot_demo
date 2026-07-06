<?php

require_once dirname(__DIR__) . '/config/config.php';

require_once APP_PATH . '/helpers/url_helper.php';
require_once APP_PATH . '/helpers/session_helper.php';

require_once APP_PATH . '/core/Database.php';
require_once APP_PATH . '/core/Controller.php';
require_once APP_PATH . '/core/App.php';

$app = new App();