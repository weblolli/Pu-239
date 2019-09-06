<?php

declare(strict_types = 1);

use DI\DependencyException;
use DI\NotFoundException;
use Pu239\Bookmark;
use Pu239\Cache;
use Pu239\Image;
use Pu239\Session;
use Spatie\Image\Exceptions\InvalidManipulation;

require_once INCL_DIR . 'function_categories.php';

/**
 * @param $num
 *
 * @return string
 * @return string
 */
function linkcolor($num)
{
    if (!$num) {
        return 'red';
    }

    return 'pink';
}

/**
 * @param $text
 * @param $char
 * @param $link
 *
 * @throws NotFoundException
 * @throws \Envms\FluentPDO\Exception
 * @throws DependencyException
 * @throws InvalidManipulation
 *
 * @return mixed|string|string[]|null
 */
function readMore($text, $char, $link)
{
    return strlen($text) > $char ? '<p>' . substr(format_comment($text), 0, $char - 1) . "...</p><br><p><a href='$link' class='has-text-primary'>Read more...</a></p>" : format_comment($text);
}

/**
 * @param array  $res
 * @param array  $curuser
 * @param string $variant
 *
 * @throws DependencyException
 * @throws NotFoundException
 * @throws \Envms\FluentPDO\Exception
 * @throws InvalidManipulation
 *
 * @return string
 */
function torrenttable(array $res, array $curuser, string $variant = 'index')
{
    global $container, $site_config, $lang;

    $htmlout = $prevdate = $nuked = $free_color = $slots_check = $private = '';
    $link1 = $link2 = $link3 = $link4 = $link5 = $link6 = $link7 = $link8 = $link9 = '';
    $lookup = $oldlink = [];
    $query_strings = explode('&', $_SERVER['QUERY_STRING']);
    foreach ($query_strings as $query_string) {
        $term = explode('=', $query_string);
        $ignore = [
            'sa',
            'st',
        ];
        if (!in_array($term[0], $ignore) && !empty($term[1])) {
            $lookup[] = "{$term[0]}={$term[1]}";
        }
    }
    $lookup = !empty($lookup) ? implode('&amp;', $lookup) . '&amp;' : '';
    require_once INCL_DIR . 'function_bbcode.php';
    require_once CLASS_DIR . 'class_user_options_2.php';
    require_once INCL_DIR . 'function_torrent_hover.php';
    $lang = array_merge($lang, load_language('index'));
    $cache = $container->get(Cache::class);
    $free = $cache->get('site_events_');
    $free_display = '';
    $staff_tools = $curuser['class'] >= $site_config['allowed']['fast_edit'] || $curuser['class'] >= $site_config['allowed']['fast_delete'] || $curuser['class'] >= $site_config['allowed']['staff_picks'];
    if (!empty($free)) {
        foreach ($free as $fl) {
            if (!empty($fl['modifier'])) {
                switch ($fl['modifier']) {
                    case 1:
                        $free_display = '[Free]';
                        break;

                    case 2:
                        $free_display = '[Double]';
                        break;

                    case 3:
                        $free_display = '[Free and Double]';
                        break;

                    case 4:
                        $free_display = '[Silver]';
                        break;
                }
                $all_free_tag = $fl['modifier'] != 0 && ($fl['expires'] > TIME_NOW || $fl['expires'] == 1) ? ' 
            <a class="info" href="#">
            <b>' . $free_display . '</b>
            <span>' . ($fl['expires'] != 1 ? '
            Expires: ' . get_date((int) $fl['expires'], 'DATE') . '<br>
            (' . mkprettytime($fl['expires'] - TIME_NOW) . ' to go)</span></a><br>' : 'Unlimited</span></a><br>') : '';
            }
        }
    }
    foreach ($_GET as $key => $var) {
        if (in_array($key, [
            'sort',
            'type',
        ])) {
            continue;
        }
        if (is_array($var)) {
            foreach ($var as $s_var) {
                $oldlink[] = sprintf('%s=%s', urlencode($key) . '%5B%5D', urlencode((string) $s_var));
            }
        } else {
            $oldlink[] = sprintf('%s=%s', urlencode($key), urlencode($var));
        }
    }
    $oldlink = !empty($oldlink) ? implode('&amp;', array_map('htmlsafechars', $oldlink)) . '&amp;' : '';
    $type = isset($_GET['type']) ? $_GET['type'] : 'desc';
    for ($i = 1; $i <= 8; ++$i) {
        if (isset($_GET['sort']) && (int) $_GET['sort'] === $i) {
            $link[$i] = isset($type) && $type === 'desc' ? 'asc' : 'desc';
        } else {
            $link[$i] = 'desc';
        }
    }
    $htmlout .= "
    <div class='table-wrapper'>
        <table class='table table-bordered table-striped'>
            <thead>
                <tr>
                    <th class='has-text-centered tooltipper has-no-border-right' title='{$lang['torrenttable_type']}'>{$lang['torrenttable_type']}</th>
                    <th class='has-text-centered min-350 tooltipper has-no-border-right has-no-border-left' title='{$lang['torrenttable_name']}'><a href='{$_SERVER['PHP_SELF']}?{$oldlink}sort=1&amp;type={$link[1]}'>{$lang['torrenttable_name']}</a></th>
                    <th class='has-text-centered tooltipper w-1 has-no-border-right has-no-border-left' title='{$lang['torrenttable_download']}'><i class='icon-download icon' aria-hidden='true'></i></th>";
    $htmlout .= ($variant === 'index' ? "
                    <th class='has-text-centered tooltipper w-1 has-no-border-right has-no-border-left' title='{$lang['bookmark_goto']}'>
                        <a href='{$site_config['paths']['baseurl']}/bookmarks.php'>
                            <i class='icon-bookmark-empty icon' aria-hidden='true'></i>
                        </a>
                    </th>" : '');
    if ($variant === 'mytorrents') {
        $htmlout .= "
                    <th class='has-text-centered tooltipper' title='{$lang['torrenttable_edit']}'>{$lang['torrenttable_edit']}</th>
                    <th class='has-text-centered tooltipper' title='{$lang['torrenttable_visible']}'>{$lang['torrenttable_visible']}</th>";
    }
    $htmlout .= "
                    <th class='has-text-centered tooltipper w-1 has-no-border-right has-no-border-left' title='{$lang['torrenttable_files']}'><a href='{$_SERVER['PHP_SELF']}?{$oldlink}sort=2&amp;type={$link[2]}'><i class='icon-docs icon' aria-hidden='true'></i></a></th>
                    <th class='has-text-centered tooltipper w-1 has-no-border-right has-no-border-left' title='{$lang['torrenttable_comments']}'><a href='{$_SERVER['PHP_SELF']}?{$oldlink}sort=3&amp;type={$link[3]}'><i class='icon-commenting-o icon has-text-info' aria-hidden='true'></i></a></th>
                    <th class='has-text-centered tooltipper w-1 has-no-border-right has-no-border-left' title='{$lang['torrenttable_size']}'><a href='{$_SERVER['PHP_SELF']}?{$oldlink}sort=4&amp;type={$link[4]}'><i class='icon-doc icon' aria-hidden='true'></i></a></th>
                    <th class='has-text-centered tooltipper w-1 has-no-border-right has-no-border-left' title='{$lang['torrenttable_snatched']}'><a href='{$_SERVER['PHP_SELF']}?{$oldlink}sort=5&amp;type={$link[5]}'><i class='icon-ok-circled2 icon has-text-success' aria-hidden='true'></i></a></th>
                    <th class='has-text-centered tooltipper w-1 has-no-border-right has-no-border-left' title='{$lang['torrenttable_seeders']}'><a href='{$_SERVER['PHP_SELF']}?{$oldlink}sort=7&amp;type={$link[6]}'><i class='icon-up-big icon has-text-success' aria-hidden='true'></i></a></th>
                    <th class='has-text-centered tooltipper w-1 has-no-border-right has-no-border-left' title='{$lang['torrenttable_leechers']}'><a href='{$_SERVER['PHP_SELF']}?{$oldlink}sort=7&amp;type={$link[7]}'><i class='icon-down-big icon has-text-danger' aria-hidden='true'></i></a></th>";
    if ($variant === 'index') {
        $htmlout .= "
                    <th class='has-text-centered tooltipper w-1 has-no-border-right has-no-border-left' title='{$lang['torrenttable_uppedby']}'><a href='{$_SERVER['PHP_SELF']}?{$oldlink}sort=8&amp;type={$link[8]}'><i class='icon-user icon' aria-hidden='true'></i></a></th>";
    }
    $htmlout .= "
                    <th class='has-text-centered tooltipper w-1 " . ($staff_tools ? 'has-no-border-right' : '') . " has-no-border-left' title='{$lang['torrenttable_to_go_def']}'><i class='icon-percent icon' aria-hidden='true'></i></th>";
    if ($curuser['class'] >= $site_config['allowed']['fast_edit'] || $curuser['class'] >= $site_config['allowed']['fast_delete'] || $curuser['class'] >= $site_config['allowed']['staff_picks']) {
        $htmlout .= "
                    <th class='has-text-centered has-text-success w-5 tooltipper has-no-border-left' title='{$lang['torrenttable_tools']}'><i class='icon-tools icon' aria-hidden='true'></i></th>";
    }
    $htmlout .= '
            </tr>
        </thead>
        <tbody>';
    $categories = genrelist(false);
    $change = [];
    foreach ($categories as $key => $value) {
        $change[$value['id']] = [
            'id' => $value['id'],
            'name' => $value['name'],
            'image' => $value['image'],
            'parent_id' => $value['parent_id'],
        ];
    }
    $images_class = $container->get(Image::class);
    foreach ($res as $row) {
        if ($curuser['opt2'] & user_options_2::SPLIT) {
            if (get_date((int) $row['added'], 'DATE') == $prevdate) {
                $cleandate = '';
            } else {
                $htmlout .= "
            <tr>
                <td colspan='12' class='colhead has-text-left'><b>{$lang['torrenttable_upped']} " . get_date((int) $row['added'], 'DATE') . '</b></td>
            </tr>';
            }
            $prevdate = get_date((int) $row['added'], 'DATE');
        }
        if ($row['to_go'] == -1) {
            $to_go = "<div class='has-text-danger tooltipper' title='{$lang['torrenttable_never_snatched']}'>--</div>";
        } elseif ($row['to_go'] == 1) {
            $to_go = "<div class='has-text-success tooltipper' title='{$lang['torrenttable_completed']}'>100%</div>";
        } else {
            $to_go = "<div class='has-text-warning tooltipper' title='{$lang['torrenttable_incomplete']}'>" . number_format((int) $row['to_go'], 1) . '%</div>';
        }
        $row['cat_name'] = format_comment($change[$row['category']]['name']);
        $row['cat_pic'] = format_comment($change[$row['category']]['image']);
        $row['parent_id'] = $change[$row['category']]['parent_id'];
        $id = $row['id'];
        $htmlout .= "
                    <tr>
                    <td class='has-text-centered has-no-border-right'>";
        if (isset($row['cat_name'])) {
            $htmlout .= "<a href='{$site_config['paths']['baseurl']}/browse.php?{$lookup}" . (!empty($row['parent_id']) ? "cats[]={$row['parent_id']}&amp;" : '') . 'cats[]=' . $row['category'] . "'>";
            if (isset($row['cat_pic']) && $row['cat_pic'] != '') {
                $htmlout .= "<img src='{$site_config['paths']['images_baseurl']}caticons/" . get_category_icons() . "/{$row['cat_pic']}' class='tooltipper' alt='{$row['cat_name']}' title='{$row['cat_name']}'>";
            } else {
                $htmlout .= format_comment($row['cat_name']);
            }
            $htmlout .= '</a>';
        } else {
            $htmlout .= '-';
        }
        $htmlout .= '</td>';
        $year = !empty($row['year']) ? " ({$row['year']})" : '';
        $dispname = format_comment($row['name']) . $year;
        $staff_pick = $row['staff_picks'] > 0 ? "
            <span id='staff_pick_{$row['id']}'>
                <img src='{$site_config['paths']['images_baseurl']}staff_pick.png' class='tooltipper emoticon is-2x' alt='{$lang['torrenttable_staff_pick']}' title='{$lang['torrenttable_staff_pick']}'>
            </span>" : "
            <span id='staff_pick_{$row['id']}'>
            </span>";

        $imdb_info = '';
        if (in_array($row['category'], $site_config['categories']['movie']) || in_array($row['category'], $site_config['categories']['tv'])) {
            $percent = !empty($row['rating']) ? $row['rating'] * 10 : 0;
            $imdb_info = "
                    <div class='star-ratings-css tooltipper' title='{$percent}% {$lang['torrenttable_voters']}'>
                        <div class='star-ratings-css-top' style='width: {$percent}%'><span>★</span><span>★</span><span>★</span><span>★</span><span>★</span></div>
                        <div class='star-ratings-css-bottom'><span>★</span><span>★</span><span>★</span><span>★</span><span>★</span></div>
                    </div>";
        }
        $smalldescr = (!empty($row['description']) ? '<div><i>[' . format_comment($row['description']) . ']</i></div>' : '');
        if (empty($row['poster']) && !empty($row['imdb_id'])) {
            $row['poster'] = $images_class->find_images($row['imdb_id']);
        }
        $poster = empty($row['poster']) ? "<img src='{$site_config['paths']['images_baseurl']}noposter.png' class='tooltip-poster' alt='Poster'>" : "<img src='" . url_proxy($row['poster'], true, 250) . "' class='tooltip-poster' alt='Poster'>";
        $user_rating = empty($row['rating_sum']) ? '' : ratingpic($row['rating_sum'] / $row['num_ratings']);
        $descr = '';
        if (!empty($row['descr'])) {
            $descr = str_replace('"', '&quot;', readMore($row['descr'], 500, $site_config['paths']['baseurl'] . '/details.php?id=' . $row['id'] . '&amp;hit=1'));
            $descr = preg_replace('/\[img\].*?\[\/img\]\s+/', '', $descr);
            $descr = preg_replace('/\[img=.*?\]\s+/', '', $descr);
        }
        $htmlout .= "
            <td class='has-no-border-right has-no-border-left'>
                <div class='level-wide min-350'>
                    <div>
                        <a class='is-link' href='{$site_config['paths']['baseurl']}/details.php?";
        if ($variant === 'mytorrents') {
            $htmlout .= 'returnto=' . urlencode($_SERVER['REQUEST_URI']) . '&amp;';
        }
        $htmlout .= "id=$id";
        if ($variant === 'index') {
            $htmlout .= '&amp;hit=1';
        }
        $htmlout .= "'>";
        $icons = $top_icons = [];
        $top_icons[] = $row['added'] >= $curuser['last_browse'] ? "<span class='tag is-danger'>New!</span>" : '';
        $icons[] = $row['sticky'] === 'yes' ? "<img src='{$site_config['paths']['images_baseurl']}sticky.gif' class='tooltipper icon' alt='{$lang['torrenttable_sticky']}' title='{$lang['torrenttable_sticky']}'>" : '';
        $icons[] = $row['vip'] == 1 ? "<img src='{$site_config['paths']['images_baseurl']}star.png' class='tooltipper icon' alt='{$lang['torrenttable_vip']}' title='<div class=\"size_5 has-text-centered has-text-success\">VIP</div>{$lang['torrenttable_vip']}'>" : '';
        $icons[] = !empty($row['youtube']) ? "<a href='" . htmlsafechars($row['youtube']) . "' target='_blank'><i class='icon-youtube icon' aria-hidden='true'></i></a>" : '';
        $icons[] = $row['release_group'] === 'scene' ? "<img src='{$site_config['paths']['images_baseurl']}scene.gif' class='tooltipper icon' title='Scene' alt='Scene'>" : ($row['release_group'] === 'p2p' ? " <img src='{$site_config['paths']['images_baseurl']}p2p.gif' class='tooltipper icon' title='P2P' alt='P2P'>" : '');
        $icons[] = !empty($row['checked_by_username']) ? "<i class='icon-thumbs-up icon has-text-success tooltipper' aria-hidden='true' title='<div class=\"size_5 has-text-primary has-text-centered\">CHECKED</div><span class=\"right10\">By: </span><span>" . format_comment($row['checked_by_username']) . '</span><br><span class="right10">On: </span><span>' . get_date((int) $row['checked_when'], 'DATE') . "</span>'></i>" : '';
        $icons[] = $row['free'] != 0 ? "<img src='{$site_config['paths']['images_baseurl']}gold.png' class='tooltipper icon' alt='{$lang['torrenttable_free']}' title='<div class=\"has-text-centered size_5 has-text-success\">{$lang['torrenttable_free']}</div><div class=\"has-text-centered\">" . ($row['free'] > 1 ? 'Expires: ' . get_date((int) $row['free'], 'DATE') . '<br>(' . mkprettytime($row['free'] - TIME_NOW) . ' to go)</div>' : '<div class="has-text-centered">Unlimited</div>') . "'>" : '';
        $icons[] = $row['silver'] != 0 ? "<img src='{$site_config['paths']['images_baseurl']}silver.png' class='tooltipper icon' alt='{$lang['torrenttable_silver']}' title='<div class=\"has-text-centered size_5 has-text-success\">{$lang['torrenttable_silver']}</div><div class=\"has-text-centered\">" . ($row['silver'] > 1 ? 'Expires: ' . get_date((int) $row['silver'], 'DATE') . '<br>(' . mkprettytime($row['silver'] - TIME_NOW) . ' to go)</div>' : '<div class="has-text-centered">Unlimited</div>') . "'>" : '';
        $title = "
            <div class='dt-tooltipper-large' data-tooltip-content='#desc_{$row['id']}_tooltip'>
                <i class='icon-search icon' aria-hidden='true'></i>
                <div class='tooltip_templates'>
                    <div id='desc_{$row['id']}_tooltip'>
                        " . format_comment($descr, false, true, false) . '
                    </div>
                </div>
            </div>';

        $icons[] = !empty($row['descr']) ? $title : '';
        $icons[] = $row['freetorrent'] > 0 ? '<img src="' . $site_config['paths']['images_baseurl'] . 'freedownload.gif" class="tooltipper icon" alt="Free Slot" title="Free Slot in Use">' : '';
        $icons[] = $row['doubletorrent'] > 0 ? '<img src="' . $site_config['paths']['images_baseurl'] . 'doubleseed.gif" class="tooltipper icon" alt="Double Upload Slot" title="Double Upload Slot in Use">' : '';
        $icons[] = $row['nuked'] === 'yes' ? "<img src='{$site_config['paths']['images_baseurl']}nuked.gif' class='tooltipper icon' alt='Nuked'  class='has-text-centered' title='<div class=\"size_5 has-text-centered has-text-danger\">Nuked</div><span class=\"right10\">Reason: </span>" . format_comment($row['nukereason']) . "'>" : '';
        $icons[] = $row['bump'] === 'yes' ? "<img src='{$site_config['paths']['images_baseurl']}forums/up.gif' class='tooltipper icon' alt='Re-Animated torrent' title='<div class=\"size_5 has-text-centered has-text-success\">Bumped</div><span class=\"has-text-centered\">This torrent was ReAnimated!</span>'>" : '';

        $genres = '';
        if (!empty($row['newgenre'])) {
            $genres = $row['newgenre'];
            $newgenre = [];
            $row['newgenre'] = explode(',', $row['newgenre']);
            foreach ($row['newgenre'] as $foo) {
                $newgenre[] = "<a href='{$site_config['paths']['baseurl']}/browse.php?{$lookup}sg=" . strtolower(trim($foo)) . "'>" . ucfirst(strtolower(trim($foo))) . '</a>';
            }
            if (!empty($newgenre)) {
                $icons[] = implode(',&nbsp;', $newgenre);
            }
        }
        $icon_string = implode(' ', array_diff($icons, ['']));
        $icon_string = !empty($icon_string) ? "<div class='level-left'>{$icon_string}</div>" : '';
        $top_icons = implode(' ', array_diff($top_icons, ['']));
        $top_icons = !empty($top_icons) ? "<div class='left10'>{$top_icons}</div>" : '';
        $name = $row['name'];
        if (!empty($row['username'])) {
            if ($row['anonymous'] === '1' && $curuser['class'] < UC_STAFF && $row['owner'] != $curuser['id']) {
                $uploader = '<span>' . get_anonymous_name() . '</span>';
                $formatted = "<i>({$uploader})</i>";
            } else {
                $uploader = "<span class='" . get_user_class_name((int) $row['class'], true) . "'>" . format_comment($row['username']) . '</span>';
                $formatted = format_username((int) $row['owner']);
            }
        } else {
            $uploader = $lang['torrenttable_unknown_uploader'];
            $formatted = "<i>({$uploader})</i>";
        }
        $block_id = "torrent_{$id}";
        $sticky = $row['sticky'] === 'yes' ? 'sticky' : '';
        $tooltip = torrent_tooltip(format_comment($dispname), $id, $block_id, $name, $poster, $uploader, $row['added'], $row['size'], $row['seeders'], $row['leechers'], $row['imdb_id'], $row['rating'], $row['year'], $row['subs'], $row['audios'], $genres, false, null, $sticky);
        $subs = $container->get('subtitles');
        $subs_array = explode('|', $row['subs']);
        $Subs = [];
        foreach ($subs_array as $k => $subname) {
            foreach ($subs as $sub) {
                if (strtolower($sub['name']) === strtolower($subname)) {
                    $Subs[] = "<a href='{$site_config['paths']['baseurl']}/browse.php?{$lookup}st=" . htmlsafechars($sub['name']) . "' class='left5'>
                                <img src='{$site_config['paths']['images_baseurl']}/{$sub['pic']}' class='tooltipper icon is-marginless' width='16' alt='" . htmlsafechars($sub['name']) . "' title='" . htmlsafechars($sub['name']) . " {$lang['torrenttable_subtitle']}'>
                               </a>";
                }
            }
        }
        $subtitles = '';
        if (!empty($Subs)) {
            $subtitles = '<span>' . implode(' ', $Subs) . '</span>';
        }
        $audios_array = !empty($row['audios']) ? explode('|', $row['audios']) : [];
        $Audios = [];
        foreach ($audios_array as $k => $subname) {
            foreach ($subs as $sub) {
                if (strtolower($sub['name']) === strtolower($subname)) {
                    $Audios[] = "<a href='{$site_config['paths']['baseurl']}/browse.php?{$lookup}sa=" . htmlsafechars($sub['name']) . "' class='right5'>
                                <img src='{$site_config['paths']['images_baseurl']}/{$sub['pic']}' class='tooltipper icon is-marginless' width='16' alt='" . htmlsafechars($sub['name']) . "' title='" . htmlsafechars($sub['name']) . " {$lang['torrenttable_audio']}'>
                               </a>";
                }
            }
        }
        $audios = '';
        if (!empty($Audios)) {
            $audios = '<span>' . implode(' ', $Audios) . '</span>';
        }
        $added = get_date((int) $row['added'], 'LONG', 0, 1);
        $htmlout .= $tooltip . "
                        </a>
                        <div class='level-left'>{$imdb_info}</div>
                    </div>
                    <div class='level left10'>
                        {$top_icons}{$staff_pick}
                    </div>
                </div>
                <div class='level-wide'>{$icon_string}{$user_rating}{$smalldescr}{$added}</div>
                <div class='level-wide top5'>{$audios}{$subtitles}</div>
            </td>";
        $session = $container->get(Session::class);
        $scheme = $session->get('scheme') === 'http' ? '' : '&amp;ssl=1';
        if ($variant === 'mytorrents') {
            $htmlout .= "
                <td>
                    <div class='level-center'>
                        <div class='flex-inrow'>
                            <a href='{$site_config['paths']['baseurl']}/download.php?torrent={$id}" . $scheme . "' class='flex-item'>
                                <i class='icon-download icon tooltipper' aria-hidden='true' title='{$lang['torrenttable_download_torrent']}'></i>
                            </a>
                        </div>
                    </div>
                </td>
                <td>
                    <div class='level-center'>
                        <div class='flex-inrow'>
                            <a href='{$site_config['paths']['baseurl']}/edit.php?id=" . $row['id'] . 'amp;returnto=' . urlencode($_SERVER['REQUEST_URI']) . "' class='flex-item'>
                                {$lang['torrenttable_edit']}
                            </a>
                        </div>
                    </div>
                </td>";
        }
        $htmlout .= ($variant === 'index' ? "
                <td class='has-text-centered has-no-border-right has-no-border-left'>
                    <div class='level-center'>
                        <div class='flex-inrow'>
                            <a href='{$site_config['paths']['baseurl']}/download.php?torrent={$id}" . $scheme . "'  class='flex-item'>
                                <i class='icon-download icon tooltipper' aria-hidden='true' title='{$lang['torrenttable_download_torrent']}'></i>
                            </a>
                        </div>
                    </div>
                </td>" : '');
        if ($variant === 'mytorrents') {
            $htmlout .= "<td class='has-text-centered'>";
            if ($row['visible'] === 'no') {
                $htmlout .= "<b>{$lang['torrenttable_not_visible']}</b>";
            } else {
                $htmlout .= $lang['torrenttable_visible'];
            }
            $htmlout .= '</td>';
        }
        $bookmark = "
                <span data-tid='{$id}' data-remove='false' data-private='false' class='bookmarks tooltipper' title='{$lang['bookmark_add']}'>
                    <i class='icon-bookmark-empty icon has-text-success' aria-hidden='true'></i>
                </span>";

        $bookmark_class = $container->get(Bookmark::class);
        $book = $bookmark_class->get($curuser['id']);
        if (!empty($book)) {
            foreach ($book as $bk) {
                if ($bk['torrentid'] == $id) {
                    $bookmark = "
                    <span data-tid='{$id}' data-remove='false' data-private='false' class='bookmarks tooltipper' title='{$lang['bookmark_delete']}'>
                        <i class='icon-bookmark-empty icon has-text-danger' aria-hidden='true'></i>
                    </span>";
                }
            }
        }
        if ($variant === 'index') {
            $htmlout .= "<td class='has-text-centered has-no-border-right has-no-border-left'>{$bookmark}</td>";
        }
        if ($variant === 'index') {
            $htmlout .= "<td class='has-text-centered has-no-border-right has-no-border-left'><b><a href='{$site_config['paths']['baseurl']}/filelist.php?id=$id'>" . $row['numfiles'] . '</a></b></td>';
        } else {
            $htmlout .= "<td class='has-text-centered'><b><a href='{$site_config['paths']['baseurl']}/filelist.php?id=$id'>" . $row['numfiles'] . '</a></b></td>';
        }
        if (!$row['comments']) {
            $comments = "<a href='{$site_config['paths']['baseurl']}/details.php?id=$id&amp;hit=1&amp;tocomm=1' class='tooltipper' title='{$lang['torrenttable_comments']}'>0</a>";
        } else {
            if ($variant === 'index') {
                $comments = "<a href='{$site_config['paths']['baseurl']}/details.php?id=$id&amp;hit=1&amp;tocomm=1' class='tooltipper' title='{$lang['torrenttable_comments']}'>" . $row['comments'] . '</a>';
            } else {
                $comments = "<a href='{$site_config['paths']['baseurl']}/details.php?id=$id&amp;page=0#startcomments' class='tooltipper' title='{$lang['torrenttable_comments']}'>" . $row['comments'] . '</a>';
            }
        }
        $htmlout .= "<td class='has-text-centered has-no-border-right has-no-border-left'>{$comments}</td>";
        $htmlout .= "<td class='has-text-centered has-no-border-right has-no-border-left'>" . str_replace(' ', '<br>', mksize($row['size'])) . '</td>';
        $htmlout .= "<td class='has-text-centered has-no-border-right has-no-border-left'><a href='{$site_config['paths']['baseurl']}/snatches.php?id={$id}' class='tooltipper' title='{$lang['torrenttable_snatched']}'>" . number_format($row['times_completed']) . '</a></td>';
        if ($row['seeders']) {
            if ($variant === 'index') {
                if ($row['leechers']) {
                    $ratio = $row['seeders'] / $row['leechers'];
                } else {
                    $ratio = 1;
                }
                $seeders = "<a href='{$site_config['paths']['baseurl']}/peerlist.php?id={$id}#seeders' class='tooltipper' title='{$lang['torrenttable_seeders']}'><span style='color: " . get_slr_color($ratio) . ";'>" . $row['seeders'] . '</span></a>';
            } else {
                $seeders = "<a href='{$site_config['paths']['baseurl']}/peerlist.php?id={$id}#seeders' class='tooltipper' title='{$lang['torrenttable_seeders']}'><span class='" . linkcolor($row['seeders']) . "'>" . $row['seeders'] . '</span></a>';
            }
        } else {
            $seeders = "<a href='{$site_config['paths']['baseurl']}/peerlist.php?id={$id}#seeders' class='tooltipper' title='{$lang['torrenttable_seeders']}'>0</a>";
        }

        if ($row['leechers']) {
            if ($variant === 'index') {
                $leechers = "<a href='{$site_config['paths']['baseurl']}/peerlist.php?id={$id}#leechers' class='tooltipper' title='{$lang['torrenttable_leechers']}'>" . number_format($row['leechers']) . '</a>';
            } else {
                $leechers = "<a class='" . linkcolor($row['leechers']) . "' href='{$site_config['paths']['baseurl']}/peerlist.php?id={$id}#leechers' class='tooltipper' title='{$lang['torrenttable_leechers']}'>" . $row['leechers'] . '</a>';
            }
        } else {
            $leechers = "<a href='{$site_config['paths']['baseurl']}/peerlist.php?id={$id}#leechers' class='tooltipper' title='{$lang['torrenttable_leechers']}'>0</a>";
        }
        $htmlout .= "<td class='has-text-centered has-no-border-right has-no-border-left'>$seeders</td>";
        $htmlout .= "<td class='has-text-centered has-no-border-right has-no-border-left'>$leechers</td>";
        if ($variant === 'index') {
            $htmlout .= "<td class='has-text-centered has-no-border-right has-no-border-left'>{$formatted}</td>";
        }
        $htmlout .= "<td class='has-text-centered " . ($staff_tools ? 'has-no-border-right' : '') . " has-no-border-left'>$to_go</td>";
        if ($staff_tools) {
            $returnto = !empty($_SERVER['REQUEST_URI']) ? '&amp;returnto=' . urlencode($_SERVER['REQUEST_URI']) : '';
            $edit_link = ($curuser['class'] >= $site_config['allowed']['fast_edit'] ? "
                <span>
                    <a href='{$site_config['paths']['baseurl']}/edit.php?id=" . $row['id'] . "{$returnto}' class='tooltipper' title='Fast Edit'>
                        <i class='icon-edit icon has-text-info' aria-hidden='true'></i>
                    </a>
                </span>" : '');
            $del_link = ($curuser['class'] >= $site_config['allowed']['fast_delete'] ? "
                <span>
                    <a href='{$site_config['paths']['baseurl']}/fastdelete.php?id=" . $row['id'] . "{$returnto}' class='tooltipper' title='Fast Delete'>
                        <i class='icon-trash-empty icon has-text-danger' aria-hidden='true'></i>
                    </a>
                </span>" : '');
            $staff_pick = '';
            if ($curuser['class'] >= $site_config['allowed']['staff_picks'] && $row['staff_picks'] > 0) {
                $staff_pick = "
                <span data-id='{$row['id']}' data-pick='{$row['staff_picks']}' class='staff_pick tooltipper' title='Remove from Staff Picks'>
                    <i class='icon-star-empty icon has-text-danger' aria-hidden='true'></i>
                </span>";
            } elseif ($curuser['class'] >= $site_config['allowed']['staff_picks']) {
                $staff_pick = "
                <span data-id='{$row['id']}' data-pick='{$row['staff_picks']}' class='staff_pick tooltipper' title='Add to Staff Picks'>
                    <i class='icon-star-empty icon has-text-success' aria-hidden='true'></i>
                </span>";
            }

            $htmlout .= "
                        <td class='has-no-border-left'>
                            <div class='level-center'>
                                {$edit_link}
                                {$del_link}
                                {$staff_pick}
                            </div>
                        </td>";
        }
        $htmlout .= '</tr>';
    }
    $htmlout .= '</tbody>
            </table>
        </div>';

    return $htmlout;
}
