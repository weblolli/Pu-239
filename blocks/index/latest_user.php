<?php

declare(strict_types = 1);

use Pu239\User;

global $container, $lang, $site_config;

$user_class = $container->get(User::class);
$userid = $user_class->get_latest_user();
$latestuser = format_username((int) $userid);
$latest_user .= "
        <a id='latestuser-hash'></a>
        <div id='latestuser' class='box'>
            <div class='bordered'>
                <div class='alt_bordered bg-00 level-item is-wrapped padding20'>
                    {$lang['index_wmember']}&nbsp;{$latestuser}!
                </div>
            </div>
        </div>";
