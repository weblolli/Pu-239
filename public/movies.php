<?php

declare(strict_types = 1);

require_once __DIR__ . '/../include/bittorrent.php';
require_once INCL_DIR . 'function_users.php';
require_once INCL_DIR . 'function_bbcode.php';
require_once INCL_DIR . 'function_html.php';
require_once INCL_DIR . 'function_imdb.php';
require_once INCL_DIR . 'function_tmdb.php';
require_once INCL_DIR . 'function_tvmaze.php';
require_once INCL_DIR . 'function_bluray.php';
require_once INCL_DIR . 'function_fanart.php';
$user = check_user_status();
$lang = array_merge(load_language('global'), load_language('movies'));
$image = placeholder_image();
global $site_config;

$lists = [
    'upcoming',
    'top100',
    'theaters',
    'tv',
    'tvmaze',
    'bluray',
    'imdb_top100',
    'imdb_theaters',
];
$list = 'upcoming';
if (!empty($_GET['list']) && in_array($_GET['list'], $lists)) {
    $list = $_GET['list'];
}

switch ($list) {
    case 'bluray':
        $title = $lang['movies_bluray'];
        $pubs = get_bluray_info();
        if (is_array($pubs)) {
            $div = "
        <div class='masonry padding20'>";
            foreach ($pubs as $data) {
                $div .= generate_html($data, $lang);
            }
            $div .= '
        </div>';
            $div = main_div($div);
        } else {
            $div = main_div("<p class='has-text-centered'>{$lang['movies_bluray_down']}</p>", '', 'padding20');
        }
        $HTMLOUT = "
        <h1 class='has-text-centered'>{$lang['movies_bluray']}</h1>" . $div;

        break;

    case 'tvmaze':
        $title = $lang['movies_tvmaze'];
        $tvmaze_data = get_schedule();
        if (is_array($tvmaze_data)) {
            $today = date('Y-m-d');
            $shows = [];
            foreach ($tvmaze_data as $listing) {
                if (!empty($listing['airstamp']) && !empty($listing['airdate']) && $listing['airdate'] === $today && $listing['_embedded']['show']['language'] === 'English') {
                    $shows[] = $listing;
                }
            }

            if (is_array($shows)) {
                usort($shows, 'timeSort');
                $titles = $body = [];
                foreach ($shows as $tv) {
                    if (!empty($tv['name']) && !in_array(strtolower($tv['name']), $titles)) {
                        $poster = !empty($tv['image']['original']) ? $tv['image']['original'] : (!empty($tv['_embedded']['show']['image']['original']) ? $tv['_embedded']['show']['image']['original'] : $site_config['paths']['images_baseurl'] . 'noposter.png');
                        $airtime = strtotime($tv['airstamp']);
                        $use_12_hour = !empty($user['use_12_hour']) ? $user['use_12_hour'] : $site_config['site']['use_12_hour'];
                        $body[] = [
                            'poster' => url_proxy($poster, true, 250),
                            'placeholder' => url_proxy($poster, true, 250, null, 20),
                            'title' => $tv['_embedded']['show']['name'],
                            'ep_title' => $tv['name'],
                            'season' => $tv['season'],
                            'episode' => $tv['number'],
                            'runtime' => !empty($tv['runtime']) ? "{$tv['runtime']} minutes" : '',
                            'type' => $tv['_embedded']['show']['type'],
                            'airtime' => !empty($tv['airtime']) ? get_date((int) $airtime, 'WITHOUT_SEC', 0, 1) : '',
                            'id' => $tv['_embedded']['show']['id'],
                            'overview' => str_replace([
                                '<p>',
                                '</p>',
                                '<b>',
                                '</b>',
                                '<i>',
                                '</i>',
                            ], '', $tv['_embedded']['show']['summary']),
                        ];
                        $titles[] = strtolower($tv['_embedded']['show']['name']);
                    }
                }

                $div = "
        <h1 class='has-text-centered'>{$lang['movies_tvmaze_today']}</h1>
        <div class='masonry padding20'>";
                foreach ($body as $data) {
                    $div .= generate_html($data, $lang);
                }
                $div .= '
        </div>';

                $HTMLOUT = main_div($div);
            }
        } else {
            $HTMLOUT = "
        <h1 class='has-text-centered'{$lang['movies_tvmaze_today']}</h1>" . main_div("<p class='has-text-centered'>{$lang['movies_tvmaze_down']}</p>", '', 'padding20');
        }

        break;

    case 'tv':
        $title = $lang['movies_tvschedule'];
        $base = $today = date('Y-m-d');
        if (!empty($_GET['date'])) {
            $today = $_GET['date'];
        }
        $date = new DateTime($today);
        $yesterday = $date->modify('-1 day')
                          ->format('Y-m-d');
        $date = new DateTime($today);
        $tomorrow = $date->modify('+1 day')
                         ->format('Y-m-d');
        $date = new DateTime($today);
        $display = $date->format('l Y-m-d');

        $HTMLOUT = "
    <h1 class='has-text-centered'>{$lang['movies_tv_bydate']}</h1>
    <div class='level-center top20'>
        <a href='{$_SERVER['PHP_SELF']}?list=tv&amp;date={$yesterday}' class='tooltipper' title='{$yesterday}'>{$yesterday}</a>
        <a href='{$_SERVER['PHP_SELF']}?list=tv&amp;date={$base}' class='tooltipper' title='GoTo {$base}'><h2>{$display}</h2></a>
        <a href='{$_SERVER['PHP_SELF']}?list=tv&amp;date={$tomorrow}' class='tooltipper' title='{$tomorrow}'>{$tomorrow}</a>
    </div>";
        $tvs = get_tv_by_day($today);
        if (is_array($tvs)) {
            $titles = $body = [];
            foreach ($tvs as $tv) {
                if (!empty($tv['name']) && !in_array(strtolower($tv['name']), $titles)) {
                    $imdb_id = get_imdbid($tv['id']);
                    $poster = !empty($tv['poster_path']) ? "https://image.tmdb.org/t/p/original{$tv['poster_path']}" : $site_config['paths']['images_baseurl'] . 'noposter.png';
                    $backdrop = !empty($tv['backdrop_path']) ? "https://image.tmdb.org/t/p/original{$tv['backdrop_path']}" : '';

                    $body[] = [
                        'poster' => url_proxy($poster, true, 250),
                        'placeholder' => url_proxy($poster, true, 250, null, 20),
                        'backdrop' => url_proxy($backdrop, true),
                        'title' => $tv['name'],
                        'vote_count' => $tv['vote_count'],
                        'id' => $tv['id'],
                        'vote_average' => $tv['vote_average'],
                        'popularity' => $tv['popularity'],
                        'overview' => $tv['overview'],
                    ];
                    $titles[] = strtolower($tv['name']);
                }
            }

            $div = "
        <div class='masonry padding20'>";
            foreach ($body as $data) {
                $div .= generate_html($data, $lang);
            }
            $div .= '
        </div>';

            $HTMLOUT .= main_div($div);
        } else {
            $HTMLOUT = "
        <h1 class='has-text-centered'>{$lang['movies_tmdb_bydate']}</h1>" . main_div("<p class='has-text-centered'>{$lang['movies_tmdb_down']}</p>", '', 'padding20');
        }

        break;

    case 'theaters':
        $title = $lang['movies_tmdb_in_theaters'];
        $HTMLOUT = "
    <h1 class='has-text-centered'>{$lang['movies_tmdb_in_theaters']}</h1>";
        $movies = get_movies_in_theaters();
        if (is_array($movies)) {
            $body = "
        <div class='masonry padding20'>";
            foreach ($movies as $movie) {
                $imdb_id = get_imdbid($movie['id']);
                $movie = get_imdb_info_short($imdb_id);
                if (!empty($movie)) {
                    $body .= $movie;
                }
            }
            $body .= '
        </div>';

            $HTMLOUT .= main_div($body);
        } else {
            $HTMLOUT = "
        <h1 class='has-text-centered'>{$lang['movies_tmdb_in_theaters']}</h1>" . main_div("<p class='has-text-centered'>{$lang['movies_tmdb_down']}</p>", '', 'padding20');
        }

        break;

    case 'imdb_theaters':
        $title = $lang['movies_imdb_theaters'];
        $HTMLOUT = "
    <h1 class='has-text-centered'>{$title}</h1>";
        $movies = get_in_theaters();
        if (is_array($movies)) {
            $body = "
        <div class='masonry padding20'>";
            foreach ($movies as $imdb_id) {
                $movie = get_imdb_info_short($imdb_id);
                if (!empty($movie)) {
                    $body .= $movie;
                }
            }
            $body .= '
        </div>';

            $HTMLOUT .= main_div($body);
        } else {
            $HTMLOUT = "
        <h1 class='has-text-centered'>{$lang['movies_imdb_upcoming']}</h1>" . main_div("<p class='has-text-centered'>{$lang['movies_imdb_down']}</p>", '', 'padding20');
        }

        break;

    case 'imdb_top100':
        $title = $lang['movies_imdb_top100'];
        $HTMLOUT = "
    <h1 class='has-text-centered'>{$title}</h1>";
        $movies = get_top_movies(100);
        if (is_array($movies)) {
            $body = "
        <div class='masonry padding20'>";
            foreach ($movies as $imdb_id) {
                $movie = get_imdb_info_short($imdb_id);
                if (!empty($movie)) {
                    $body .= $movie;
                }
            }
            $body .= '
        </div>';

            $HTMLOUT .= main_div($body);
        } else {
            $HTMLOUT = "
        <h1 class='has-text-centered'>{$lang['movies_imdb_upcoming']}</h1>" . main_div("<p class='has-text-centered'>{$lang['movies_imdb_down']}</p>", '', 'padding20');
        }

        break;
    case 'top100':
        $title = $lang['movies_tmdb_top100'];
        $HTMLOUT = "
    <h1 class='has-text-centered'>{$title}</h1>";
        $movies = get_movies_by_vote_average(100);
        if (is_array($movies)) {
            $body = "
        <div class='masonry padding20'>";
            foreach ($movies as $movie) {
                $imdb_id = get_imdbid($movie['id']);
                if (!empty($imdb_id)) {
                    $movie = get_imdb_info_short($imdb_id);
                    if (!empty($movie)) {
                        $body .= $movie;
                    }
                }
            }
            $body .= '
        </div>';

            $HTMLOUT .= main_div($body);
        } else {
            $HTMLOUT = "
        <h1 class='has-text-centered'>{$lang['movies_tmdb_top100']}</h1>" . main_div("<p class='has-text-centered'>{$lang['movies_tmdb_down']}</p>", '', 'padding20');
        }

        break;

    case 'upcoming':
        $title = $lang['movies_imdb_upcoming'];
        $HTMLOUT = '';
        $imdbs = get_upcoming();
        if (is_array($imdbs)) {
            foreach ($imdbs as $key => $imdb) {
                $body = '';
                $HTMLOUT .= "
        <h1 class='has-text-centered'>{$lang['movies_imdb_upcoming']} $key</h1>";

                $body .= "
        <div class='masonry padding20'>";
                foreach ($imdb as $item) {
                    $movie = get_imdb_info_short($item);
                    if (!empty($movie)) {
                        $body .= $movie;
                    }
                }

                $body .= '
        </div>';

                $HTMLOUT .= main_div($body);
            }
        } else {
            $HTMLOUT = "
        <h1 class='has-text-centered'>{$lang['movies_imdb_upcoming']}</h1>" . main_div("<p class='has-text-centered'>{$lang['movies_imdb_down']}</p>", '', 'padding20');
        }
}
echo stdhead($title) . wrapper($HTMLOUT) . stdfoot();

function generate_html(array $data, array $lang)
{
    $html = "
     <div class='masonry-item-clean padding10 bg-04 round10'>
        <div class='dt-tooltipper-large has-text-centered' data-tooltip-content='#movie_{$data['id']}_tooltip'>
            <img src='{$data['placeholder']}' data-src='{$data['poster']}' alt='Poster' class='lazy tooltip-poster'>
            <div class='has-text-centered top10'>{$data['title']}</div>";

    if (!empty($data['airtime'])) {
        $html .= "
                    <div class='has-text-centered top10'>{$data['airtime']}</div>";
    }
    if (!empty($data['release_date'])) {
        $html .= "
            <div class='has-text-centered'>{$data['release_date']}</div>";
    }
    $html .= "
            <div class='tooltip_templates'>
                <div id='movie_{$data['id']}_tooltip' class='round10 tooltip-background' " . (!empty($data['backdrop']) ? "style='background-image: url({$data['backdrop']});'" : '') . ">
                    <div class='columns is-marginless is-paddingless'>
                        <div class='column padding10 is-4'>
                            <span>
                                <img src='{$data['placeholder']}' data-src='{$data['poster']}' alt='Poster' class='lazy tooltip-poster'>
                            </span>
                        </div>
                        <div class='column padding10 is-8'>
                            <div class='padding20 is-8 bg-09 round10 h-100'>
                                <div class='columns is-multiline'>";

    if (!empty($data['title'])) {
        $html .= "
                                    <div class='column padding5 is-4'>
                                        <span class='size_4 right10 has-text-primary has-text-wight-bold'>{$lang['movies_title']}: </span>
                                    </div>
                                    <div class='column padding5 is-8'>
                                        <span class='size_4'>" . htmlsafechars($data['title']) . '</span>
                                    </div>';
    }
    if (!empty($data['ep_title'])) {
        $html .= "
                                    <div class='column padding5 is-4'>
                                        <span class='size_4 right10 has-text-primary has-text-wight-bold'>{$lang['movies_episode_title']}: </span>
                                    </div>
                                    <div class='column padding5 is-8'>
                                        <span class='size_4'>" . htmlsafechars($data['ep_title']) . '</span>
                                    </div>';
    }
    if (!empty($data['season'])) {
        $html .= "
                                    <div class='column padding5 is-4'>
                                        <span class='size_4 right10 has-text-primary has-text-wight-bold'>{$lang['movies_season']}: </span>
                                    </div>
                                    <div class='column padding5 is-8'>
                                        <span class='size_4'>" . (int) $data['season'] . '</span>
                                    </div>';
    }
    if (!empty($data['episode'])) {
        $html .= "
                                    <div class='column padding5 is-4'>
                                        <span class='size_4 right10 has-text-primary has-text-wight-bold'>{$lang['movies_episode']}: </span>
                                    </div>
                                    <div class='column padding5 is-8'>
                                        <span class='size_4'>" . (int) $data['episode'] . '</span>
                                    </div>';
    }
    if (!empty($data['runtime'])) {
        $html .= "
                                    <div class='column padding5 is-4'>
                                        <span class='size_4 right10 has-text-primary has-text-wight-bold'>{$lang['movies_runtime']}: </span>
                                    </div>
                                    <div class='column padding5 is-8'>
                                        <span class='size_4'>" . htmlsafechars($data['runtime']) . '</span>
                                    </div>';
    }
    if (!empty($data['type'])) {
        $html .= "
                                    <div class='column padding5 is-4'>
                                        <span class='size_4 right10 has-text-primary has-text-wight-bold'>{$lang['movies_type']}: </span>
                                    </div>
                                    <div class='column padding5 is-8'>
                                        <span class='size_4'>" . htmlsafechars($data['type']) . '</span>
                                    </div>';
    }
    if (!empty($data['release_date'])) {
        $html .= "
                                    <div class='column padding5 is-4'>
                                        <span class='size_4 right10 has-text-primary has-text-wight-bold'>{$lang['movies_release_date']}: </span>
                                    </div>
                                    <div class='column padding5 is-8'>
                                        <span class='size_4'>" . htmlsafechars($data['release_date']) . '</span>
                                    </div>';
    }
    if (!empty($data['popularity'])) {
        $html .= "
                                    <div class='column padding5 is-4'>
                                        <span class='size_4 right10 has-text-primary has-text-wight-bold'>{$lang['movies_popularity']}: </span>
                                    </div>
                                    <div class='column padding5 is-8'>
                                        <span class='size_4'>" . (int) $data['popularity'] . '</span>
                                    </div>';
    }
    if (!empty($data['vote_average'])) {
        $html .= "
                                    <div class='column padding5 is-4'>
                                        <span class='size_4 right10 has-text-primary has-text-wight-bold'>{$lang['movies_votes']}: </span>
                                    </div>
                                    <div class='column padding5 is-8'>
                                        <span class='size_4'>" . (int) $data['vote_average'] . '</span>
                                    </div>';
    }
    if (!empty($data['overview'])) {
        $html .= "
                                    <div class='column padding5 is-4'>
                                        <span class='size_4 right10 has-text-primary has-text-wight-bold'>{$lang['movies_overview']}: </span>
                                    </div>
                                    <div class='column padding5 is-8'>
                                        <span class='size_4'>" . htmlsafechars($data['overview']) . '</span>
                                    </div>';
    }
    $html .= '
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div> ';

    return $html;
}
