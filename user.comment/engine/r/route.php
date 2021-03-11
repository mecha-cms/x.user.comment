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
    if (!$n = \Get::get('comment')) {
        \Alert::error('Missing %s parameter in URL.', ['<code>comment</code>']);
        ++$error;
    }
    if (!$task = \Get::get('task')) {
        \Alert::error('Missing %s parameter in URL.', ['<code>task</code>']);
        ++$error;
    }
    if (!\is_file($file = \LOT . \DS . 'comment' . \DS . $any . \DS . $n . '.page')) {
        \Alert::error('Comment does not exist.');
        ++$error;
    }
    $anchor = $state->x->comment->anchor ?? [];
    $kick = $any . $i . $url->query('&', [
        'comment' => false,
        'parent' => false,
        'task' => false
    ]);
    if ($error > 0) {
        \Guard::kick($kick . '#' . $anchor[0]);
    }
    $comment = new \Comment($file);
    $author = $comment['author'];
    $id = $comment->id;
    $data = \From::page(\file_get_contents($file));
    // Delete comment file permanently
    if ('delete' === $task) {
        if (\unlink($file)) {
            \Alert::success('Comment deleted.');
        } else {
            \Alert::error('Could not delete comment due to file system error.');
        }
        \Session::let('comment');
        \Guard::kick($kick . '#' . $anchor[0]);
    }
    // Change comment status to `-1`
    if ('hide' === $task) {
        $data['status'] = -1;
        if (
            \is_readable($file) &&
            \is_writable($file) &&
            \is_int(\file_put_contents($file, \To::page($data)))
        ) {
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
            \Alert::error('Could not remove comment due to the file system error.');
        }
        \Session::let('comment');
        \Guard::kick($kick . '#' . $anchor[0]);
    }
    // Change comment status to `1` or `2`
    if ('show' === $task) {
        $data['status'] = \Is::user() === $author ? 1 : 2;
        if (
            \is_readable($file) &&
            \is_writable($file) &&
            \is_int(\file_put_contents($file, \To::page($data)))
        ) {
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
