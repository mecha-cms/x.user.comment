<?php namespace x\user__comment;

function hit($any) {
    extract($GLOBALS, \EXTR_SKIP);
    // Normalize current path
    $path = $state->x->comment->path ?? '/comment';
    $i = $url->i;
    if ($i && \substr($any, -strlen($path)) === $path) {
        $any = \dirname($any);
        $i = $path . $i;
    }
    $error = 0;
    if (!\Is::user() || 1 !== ($user->status ?? -1)) {
        \Alert::error('Method not allowed.');
        ++$error;
    }
    if (!$name = \Get::get('name')) {
        \Alert::error('Missing %s parameter in URL.', ['<code>name</code>']);
        ++$error;
    }
    if (!$task = \Get::get('task')) {
        \Alert::error('Missing %s parameter in URL.', ['<code>task</code>']);
        ++$error;
    }
    if (!\is_file($file = \LOT . \DS . 'comment' . \DS . $any . \DS . $name . '.page')) {
        \Alert::error('Comment does not exist.');
        ++$error;
    }
    $anchor = $state->x->comment->anchor ?? [];
    $kick = '/' . $any . $i . $url->query('&', [
        'name' => false,
        'parent' => false,
        'task' => false
    ]);
    if ($error > 0) {
        \Guard::kick($kick . '#' . $anchor[0]);
    }
    $comment = new \Comment($file);
    $author = $comment['author'];
    $id = $comment->id;
    $data = \From::page(\file_get_contents($file), true);
    $has_comment_guard_extension = isset($state->x->{'comment.guard'});
    // Delete comment file permanently
    if ('delete' === $task) {
        if (\unlink($file)) {
            foreach (\g(\Path::F($file), null, true) as $k => $v) {
                if (1 === $v) {
                    \unlink($k);
                } else {
                    \rmdir($k);
                }
            }
            \Alert::success('Comment deleted.');
        } else {
            \Alert::error('Could not delete comment due to file system error.');
        }
        \Session::let('comment');
        \Guard::kick($kick . '#' . $anchor[0]);
    }
    // Change comment status to `-1`
    if ('hide' === $task) {
        if ($has_comment_guard_extension) {
            if (isset($data['email']) && \is_file($f = \LOT . \DS . 'x' . \DS . 'comment.guard' . \DS . 'email.txt')) {
                $exist = false;
                $test = $data['email'];
                foreach (\stream($f) as $v) {
                    if ($test === \trim($v)) {
                        $exist = true;
                        break;
                    }
                }
                if (!$exist) {
                    $n = 0 === \filesize($f) ? "" : "\n";
                    \file_put_contents($f, $n . $test, \FILE_APPEND | \LOCK_EX);
                }
            }
            if (isset($data['ip']) && \is_file($f = \LOT . \DS . 'x' . \DS . 'comment.guard' . \DS . 'ip.txt')) {
                $exist = false;
                $test = $data['ip'];
                foreach (\stream($f) as $v) {
                    if ($test === \trim($v)) {
                        $exist = true;
                        break;
                    }
                }
                if (!$exist) {
                    $n = 0 === \filesize($f) ? "" : "\n";
                    \file_put_contents($f, $n . $test, \FILE_APPEND | \LOCK_EX);
                }
            }
        }
        $data['status'] = -1;
        if (\is_writable($file) && \is_int(\file_put_contents($file, \To::page($data)))) {
            \Alert::success('Comment marked as spam.');
        } else {
            \Alert::error('Could not mark this comment as spam due to file system error.');
        }
        \Guard::kick($kick . '#' . \sprintf($anchor[2], $id));
    }
    // Change comment file extension to `.archive`
    if ('remove' === $task) {
        if (\rename($file, \Path::F($file) . '.archive')) {
            \Alert::success('Comment removed.');
        } else {
            \Alert::error('Could not remove comment due to file system error.');
        }
        \Session::let('comment');
        \Guard::kick($kick . '#' . $anchor[0]);
    }
    // Change comment status to `1` or `2`
    if ('show' === $task) {
        if ($has_comment_guard_extension) {
            if (isset($data['email']) && \is_file($f = \LOT . \DS . 'x' . \DS . 'comment.guard' . \DS . 'email.txt')) {
                if (\filesize($f) > 0) {
                    $lines = [];
                    $test = $data['email'];
                    foreach (\stream($f) as $v) {
                        if ($test === ($v = \trim($v))) {
                            continue;
                        }
                        $lines[] = $v;
                    }
                    \file_put_contents($f, \implode("\n", $lines), \LOCK_EX);
                }
            }
            if (isset($data['ip']) && \is_file($f = \LOT . \DS . 'x' . \DS . 'comment.guard' . \DS . 'ip.txt')) {
                if (\filesize($f) > 0) {
                    $lines = [];
                    $test = $data['ip'];
                    foreach (\stream($f) as $v) {
                        if ($test === ($v = \trim($v))) {
                            continue;
                        }
                        $lines[] = $v;
                    }
                    \file_put_contents($f, \implode("\n", $lines), \LOCK_EX);
                }
            }
        }
        $data['status'] = \Is::user() === $author ? 1 : 2;
        if (\is_writable($file) && \is_int(\file_put_contents($file, \To::page($data)))) {
            \Alert::success('Comment marked as not spam.');
        } else {
            \Alert::error('Could not mark this comment as not spam due to file system error.');
        }
        \Guard::kick($kick . '#' . \sprintf($anchor[2], $id));
    }
    \Alert::error('Unknown comment task: %s', ['<code>' . $task . '</code>']);
    \Guard::kick($kick . '#' . $anchor[0]);
}

if (\Request::is('Get')) {
    \Route::hit('.comment/*', __NAMESPACE__ . "\\hit", 10);
}