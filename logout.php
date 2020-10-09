<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

// Gibbon system-wide include
require_once './gibbon.php';

$URL = './index.php';
if (isset($_GET['timeout']) and $_GET['timeout'] == 'true') {
    $URL = './index.php?timeout=true';
}

unset($_SESION[$guid]['googleAPIAccessToken']);
unset($_SESION[$guid]['gplusuer']);

session_destroy();

$_SESSION[$guid] = null;
header("Location: {$URL}");
