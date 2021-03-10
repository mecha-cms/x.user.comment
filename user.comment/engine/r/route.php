<?php namespace x\user__comment;

function hit($any) {
    extract($GLOBALS, \EXTR_SKIP);
    $error = 0;
    $status = $user->status ?? -1;
    if (!\Is::user() || 1 !== $status) {
        \Alert::error('Method not allowed.');
        ++$error;
    }
    if (!$task = \Get::get('task')) {
        \Alert::error('Missing %s parameter in URL.', ['<code>task</code>']);
        ++$error;
    }
    if (!$file = \File::exist([
        \LOT . \DS . 'comment' . \DS . $any . '.archive',
        \LOT . \DS . 'comment' . \DS . $any . '.page'
    ])) {
        \Alert::error('Comment does not exist.');
        ++$error;
    }
    $anchor = $state->x->comment->anchor ?? [];
    $kick = $url . '/' . \dirname($any) . $url->query('&', [
        'parent' => false,
        'task' => false
    ]);
    if ($error) {
        \Guard::kick($kick . '#' . $anchor[0]);
    }
    $comment = new \Comment($file);
    $author = $comment['author'];
    $id = $comment->id;
    $data = \From::page(\file_get_contents($file), false);
    // Delete comment file permanently
    if ('delete' === $task) {
        if (\unlink($file)) {
            \Alert::success('Comment deleted.');
        } else {
            \Alert::error('Could not delete comment due to the file system error.');
        }
        \Session::let('comment');
        \Guard::kick($kick . '#' . $anchor[0]);
    // Change comment status to `-1`
    } else if ('hide' === $task) {
        $data['status'] = -1;
        if (\is_readable($file) && \is_writable($file)) {
            \file_put_contents($file, \To::page($data));
            \Alert::success('Comment marked as spam.');
        } else {
            \Alert::error('Could not mark this comment as spam due to the file system error.');
        }
        \Guard::kick($kick . '#' . \sprintf($anchor[2], $id));
    // Change comment file extension to `.archive`
    } else if ('remove' === $task) {
        if (\rename($file, \Path::F($file) . '.archive')) {
            \Alert::success('Comment removed.');
        } else {
            \Alert::error('Could not remove comment due to the file system error.');
        }
        \Session::let('comment');
        \Guard::kick($kick . '#' . $anchor[0]);
    // Change comment status to `1` or `2`
    } else if ('show' === $task) {
        $data['status'] = \Is::user() === $author ? 1 : 2;
        if (\is_readable($file) && \is_writable($file)) {
            \file_put_contents($file, \To::page($data));
            \Alert::success('Comment marked as not spam.');
        } else {
            \Alert::error('Could not mark this comment as not spam due to the file system error.');
        }
        \Guard::kick($kick . '#' . \sprintf($anchor[2], $id));
    }
    \Alert::error('Unknown comment task: %s', ['<code>' . $task . '</code>']);
    \Guard::kick($kick . '#' . $anchor[0]);
}

if (\Request::is('Get')) {
    \Route::hit('.comment/*', __NAMESPACE__ . "\\hit", 10);
}
