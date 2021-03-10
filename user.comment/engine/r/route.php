<?php namespace x\user__comment\route;

function tasks($task, $token, $any) {
    extract($GLOBALS, \EXTR_SKIP);
    $error = 0;
    if (empty($token) || !\Guard::check($token, 'comment')) {
        \Alert::error('Invalid token.');
        ++$error;
    }
    if (!$file = \File::exist([
        \LOT . \DS . 'comment' . \DS . $any . '.archive',
        \LOT . \DS . 'comment' . \DS . $any . '.page'
    ])) {
        \Alert::error('Comment does not exist.');
        ++$error;
    }
    if ($error) {
        \Guard::kick($url . '/' . \dirname($any) . $url->query);
    }
    $comment = new \Comment($file);
    $author = $comment['author'];
    $id = $comment->id;
    $data = \From::page(\file_get_contents($file), false);
    if ('delete' === $task) {
        if (\unlink($file)) {
            \Alert::success('Comment deleted.');
        } else {
            \Alert::error('Could not delete comment.');
        }
        \Guard::kick($url . '/' . \dirname($any) . $url->query . '#' . $state->x->comment->anchor[0]);
    } else if ('hide' === $task) {
        $data['status'] = -1;
        if (\is_readable($file) && \is_writable($file)) {
            \file_put_contents($file, \To::page($data));
            \Alert::success('Comment marked as spam.');
        } else {
            \Alert::error('Could not mark this comment as spam.');
        }
        \Guard::kick($url . '/' . \dirname($any) . $url->query . '#' . \sprintf($state->x->comment->anchor[2], $id));
    } else if ('remove' === $task) {
        if (\rename($file, \Path::F($file) . '.draft')) {
            \Alert::success('Comment removed.');
        } else {
            \Alert::error('Could not remove comment.');
        }
        \Guard::kick($url . '/' . \dirname($any) . $url->query . '#' . $state->x->comment->anchor[0]);
    } else if ('show' === $task) {
        $data['status'] = \Is::user() === $author ? 1 : 2;
        if (\is_readable($file) && \is_writable($file)) {
            \file_put_contents($file, \To::page($data));
            \Alert::success('Comment marked as not spam.');
        } else {
            \Alert::error('Could not mark this comment as not spam.');
        }
        \Guard::kick($url . '/' . \dirname($any) . $url->query . '#' . \sprintf($state->x->comment->anchor[2], $id));
    }
    \Alert::error('Unknown comment task: %s', ['<code>' . $task . '</code>']);
    \Guard::kick($url . '/' . \dirname($any) . $url->query . '#' . $state->x->comment->anchor[0]);
}

\Route::set('.comment-tasks/:task/:token/*', __NAMESPACE__ . "\\tasks", 10);
