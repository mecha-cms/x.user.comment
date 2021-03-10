<?php namespace x\user__comment;

function commentTasks($tasks, $page) {
    extract($GLOBALS, \EXTR_SKIP);
    if (\Is::user() && 1 === $user->status) {
        if (-1 !== $this->status) {
            $tasks['hide'] = [
                0 => 'a',
                1 => \i('Mark as Spam'),
                2 => [
                    'href' => \strtr($page->url, [
                        $url . '/' => $url . '/.comment-tasks/hide/' . \Guard::token('comment') . '/'
                    ]) . '/' . $this->name . $url->query,
                    'title' => \i('Mark this comment as spam')
                ]
            ];
        } else {
            $tasks['show'] = [
                0 => 'a',
                1 => \i('Mark as Not Spam'),
                2 => [
                    'href' => \strtr($page->url, [
                        $url . '/' => $url . '/.comment-tasks/show/' . \Guard::token('comment') . '/'
                    ]) . '/' . $this->name . $url->query,
                    'title' => \i('Mark this comment as not spam')
                ]
            ];
        }
        if (!empty($this->comments->count())) {
            return $tasks; // Hide delete and remove task if current comment has children
        }
        $tasks['remove'] = [
            0 => 'a',
            1 => \i('Remove'),
            2 => [
                'href' => \strtr($page->url, [
                    $url . '/' => $url . '/.comment-tasks/remove/' . \Guard::token('comment') . '/'
                ]) . '/' . $this->name . $url->query,
                'title' => \i('Remove this comment.')
            ]
        ];
        $tasks['delete'] = [
            0 => 'a',
            1 => \i('Delete'),
            2 => [
                'href' => \strtr($page->url, [
                    $url . '/' => $url . '/.comment-tasks/delete/' . \Guard::token('comment') . '/'
                ]) . '/' . $this->name . $url->query,
                'title' => \i('Delete this comment permanently')
            ]
        ];
    }
    return $tasks;
}

function commentsForm($fields) {
    extract($GLOBALS, \EXTR_SKIP);
    if ($author = \Is::user()) {
        unset($fields['author'], $fields['email'], $fields['link']);
        $fields = [
            'title' => [
                0 => 'h3',
                1 => i('Commenting as %s', '<a href="' . $user->url . '" rel="nofollow">' . $user . '</a>')
            ]
        ] + $fields;
        $fields['author'] = '<input name="comment[author]" type="hidden" value="' . $author . '">';
    }
    return $fields;
}

function commentsFormTasks($tasks) {
    extract($GLOBALS, \EXTR_SKIP);
    $comment_state = $state->x->comment ?? [];
    $user_state = $state->x->user ?? [];
    $tasks['user'] = [
        0 => 'span',
        1 => (new \HTML([
            0 => 'a',
            1 => \Is::user() ?: \i('Log In'),
            2 => [
                'href' => $url . ($user_state->guard->path ?? $user_state->path) . $url->query('&', [
                    'kick' => \trim($url->path, '/') . $url->query . '#' . $comment_state->anchor[0]
                ])
            ]
        ])),
        2 => ['class' => 'button is:user']
    ];
    return $tasks;
}

\Hook::set('comment-tasks', __NAMESPACE__ . "\\commentTasks", 10.1);
\Hook::set('comments-form', __NAMESPACE__ . "\\commentsForm", 10.1);
\Hook::set('comments-form-tasks', __NAMESPACE__ . "\\commentsFormTasks", 10.1);