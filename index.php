<?php

namespace x {
    // Loading asset(s)…
    \Hook::set('content', function() use($state) {
        if (!empty($state->is->page) && !empty($state->has->page)) {
            $z = \defined("\\TEST") && \TEST ? '.' : '.min.';
            \class_exists("\\Asset") && \Asset::set(__DIR__ . \D . 'index' . $z . 'css', 20.2);
        }
    }, -1);
}

namespace x\user__comment {
    function comment($y) {
        \extract($GLOBALS, \EXTR_SKIP);
        $name = $this->name;
        $route = \trim($state->x->comment->route ?? 'comment', '/');
        $to = \strtr(\strtok($this->url, '?&#'), [
            $url . '/' => $url . '/comment/'
        ]);
        if (\Is::user(1)) {
            if (isset($y[1]['footer'][1]['tasks'][1])) {
                $spam = -1 === $this->status;
                $y[1]['footer'][1]['tasks'][1][$spam ? 'show' : 'hide'] = [
                    0 => 'li',
                    1 => [
                        'link' => [
                            0 => 'a',
                            1 => \i(($spam ? 'Not ' : "") . 'Spam'),
                            2 => [
                                'href' => $to . $url->query([
                                    'name' => $name,
                                    'parent' => null,
                                    'task' => $spam ? 'show' : 'hide',
                                    'token' => \token('comment')
                                ]),
                                'title' => \i('Mark this comment as ' . ($spam ? 'not ' : "") . 'spam')
                            ]
                        ]
                    ]
                ];
                // Add `delete` and `remove` task(s) only to comment(s) that have no children
                if (!empty($this->comments->count())) {
                    return $y;
                }
                $y[1]['footer'][1]['tasks'][1]['remove'] = [
                    0 => 'li',
                    1 => [
                        'link' => [
                            0 => 'a',
                            1 => \i('Remove'),
                            2 => [
                                'href' => $to . $url->query([
                                    'name' => $name,
                                    'parent' => null,
                                    'task' => 'remove',
                                    'token' => \token('comment')
                                ]),
                                'title' => \i('Remove this comment')
                            ]
                        ]
                    ]
                ];
                $y[1]['footer'][1]['tasks'][1]['delete'] = [
                    0 => 'li',
                    1 => [
                        'link' => [
                            0 => 'a',
                            1 => \i('Delete'),
                            2 => [
                                'href' => $to . $url->query([
                                    'name' => $name,
                                    'parent' => null,
                                    'task' => 'delete',
                                    'token' => \token('comment')
                                ]),
                                'title' => \i('Delete this comment permanently')
                            ]
                        ]
                    ]
                ];
            }
        }
        return $y;
    }
    function form($y) {
        // TODO
        return $y;
    }
    function route($content, $path, $query, $hash) {
        if ('GET' === $_SERVER['REQUEST_METHOD']) {
            // Remove URL query string associated with this extension from the redirect link
            \Hook::set('kick', function($to) {
                if (false === \strpos($to, '?')) {
                    return $to;
                }
                [$path, $query, $hash] = \preg_split('/[?#]/', $to);
                $query = \To::query(\array_replace_recursive((array) \From::query($query), [
                    'name' => null,
                    'task' => null,
                    'token' => null
                ]));
                return $path . $query . ("" !== $hash ? '#' . $hash : "");
            });
            $can_alert = \class_exists("\\Alert");
            $error = 0;
            $name = $_GET['name'] ?? null;
            $task = $_GET['task'] ?? null;
            $token = $_GET['token'] ?? null;
            foreach (['name', 'task', 'token'] as $v) {
                ${$v} = $_GET[$v] ?? null;
                if (empty(${$v})) {
                    $can_alert && \Alert::error('Missing %s query in URL.', [$v]);
                    ++$error;
                }
            }
            $folder = \LOT . \D . 'comment' . \strtr($path, '/', \D);
            $file = $name ? \exist([
                $folder . \D . $name . '.archive',
                $folder . \D . $name . '.page'
            ], 1) : false;
            if (!$file) {
                $can_alert && \Alert::error('Comment does not exist.');
                ++$error;
            }
            if (!$token || !\check($token, 'comment')) {
                $can_alert && \Alert::error('Invalid token.');
                ++$error;
            }
            // All passed the check(s)!
            if (!$error) {
                $has_comment_guard = null !== \State::get("x.comment\\.guard");
                // Change comment status to `-1`
                if ('hide' === $task) {
                    $comment = new \Comment($file);
                    $data = \From::page(\file_get_contents($file), true);
                    $data['status'] = -1;
                    if (false !== \file_put_contents($file, \To::page($data))) {
                        $can_alert && \Alert::success('Comment marked as spam.');
                        if ($has_comment_guard) {
                            if (isset($data['email']) && \is_file($rows = \LOT . \D . 'x' . \D . 'comment.guard' . \D . 'email.txt')) {
                                $exist = false;
                                $test = $data['email'];
                                foreach (\stream($rows) as $row) {
                                    if ($test === \trim($row)) {
                                        $exist = true;
                                        break;
                                    }
                                }
                                if (!$exist) {
                                    $n = 0 === \filesize($rows) ? "" : "\n";
                                    // Add email data to the black list
                                    if (false !== \file_put_contents($rows, $n . $test, \FILE_APPEND | \LOCK_EX)) {
                                        $can_alert && \Alert::info('Email address %s has been added to the black list.', ['<em>' . $test . '</em>']);
                                    }
                                }
                            }
                            if (isset($data['ip']) && \is_file($rows = \LOT . \D . 'x' . \D . 'comment.guard' . \D . 'ip.txt')) {
                                $exist = false;
                                $test = $data['ip'];
                                foreach (\stream($rows) as $row) {
                                    if ($test === \trim($row)) {
                                        $exist = true;
                                        break;
                                    }
                                }
                                if (!$exist) {
                                    $n = 0 === \filesize($rows) ? "" : "\n";
                                    // Add IP data to the black list
                                    if (false !== \file_put_contents($rows, $n . $test, \FILE_APPEND | \LOCK_EX)) {
                                        $can_alert && \Alert::info('IP address %s has been added to the black list.', ['<em>' . $test . '</em>']);
                                    }
                                }
                            }
                        }
                    } else {
                        $can_alert && \Alert::error('Could not mark this comment as spam due to file system error.');
                    }
                    \kick($comment->url);
                }
                // Change comment status to `1` or `2`
                if ('show' === $task) {
                    $comment = new \Comment($file);
                    $data = \From::page(\file_get_contents($file), true);
                    $data['status'] = \Is::user() === $comment['author'] ? 1 : 2;
                    if (false !== \file_put_contents($file, \To::page($data))) {
                        $can_alert && \Alert::success('Comment marked as not spam.');
                        if ($has_comment_guard) {
                            if (isset($data['email']) && \is_file($rows = \LOT . \D . 'x' . \D . 'comment.guard' . \D . 'email.txt')) {
                                if (\filesize($rows) > 0) {
                                    $list = [];
                                    $test = $data['email'];
                                    foreach (\stream($rows) as $row) {
                                        if ($test === ($row = \trim($row))) {
                                            continue;
                                        }
                                        $list[] = $row;
                                    }
                                    if (false !== \file_put_contents($rows, \implode("\n", $list), \LOCK_EX)) {
                                        $can_alert && \Alert::info('Email address %s has been removed from the black list.', ['<em>' . $test . '</em>']);
                                    }
                                }
                            }
                            if (isset($data['ip']) && \is_file($rows = \LOT . \D . 'x' . \D . 'comment.guard' . \D . 'ip.txt')) {
                                if (\filesize($rows) > 0) {
                                    $list = [];
                                    $test = $data['ip'];
                                    foreach (\stream($rows) as $row) {
                                        if ($test === ($row = \trim($row))) {
                                            continue;
                                        }
                                        $list[] = $row;
                                    }
                                    if (false !== \file_put_contents($rows, \implode("\n", $list), \LOCK_EX)) {
                                        $can_alert && \Alert::info('IP address %s has been removed from the black list.', ['<em>' . $test . '</em>']);
                                    }
                                }
                            }
                        }
                    } else {
                        $can_alert && \Alert::error('Could not mark this comment as not spam due to file system error.');
                    }
                    \kick($comment->url);
                }
                // Delete comment file permanently
                if ('delete' === $task) {
                    if (\unlink($file)) {
                        foreach (\g(\dirname($file) . \D . \pathinfo($file, \PATHINFO_FILENAME), null, true) as $k => $v) {
                            if (1 === $v) {
                                \unlink($k);
                            } else {
                                \rmdir($k);
                            }
                        }
                        $can_alert && \Alert::success('Comment deleted.');
                    } else {
                        $can_alert && \Alert::error('Could not delete comment due to file system error.');
                    }
                    \kick($path . $query . '#comment');
                }
                // Change comment file extension to `.archive`
                if ('remove' === $task) {
                    if (\rename($file, \dirname($file) . \D . \pathinfo($file, \PATHINFO_FILENAME) . '.archive')) {
                        $can_alert && \Alert::success('Comment removed.');
                    } else {
                        $can_alert && \Alert::error('Could not remove comment due to file system error.');
                    }
                    \kick($path . $query . '#comment');
                }
                $can_alert && \Alert::error('Invalid comment task.');
            }
        }
        // Proceed to the default comment route!
        // You should get an error message stating that method is not allowed.
        // This is because the default comment route only accepts `POST` request.
        return $content;
    }
    \Hook::set('route.comment', __NAMESPACE__ . "\\route", 0);
    \Hook::set('y.comment', __NAMESPACE__ . "\\comment", 10);
    \Hook::set('y.form.comment', __NAMESPACE__ . "\\form", 10);
}