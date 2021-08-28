<?php namespace x\user__comment;

function comment_tasks($tasks, $page) {
    extract($GLOBALS, \EXTR_SKIP);
    $i = $url->i;
    $i = $i ? ($state->x->comment->path ?? '/comment') . $i : "";
    $link = \strtr($page->url, [
        $url . '/' => $url . '/.comment/'
    ]) . $i;
    $name = $this->name;
    if (\Is::user() && 1 === $user->status) {
        if (-1 === $this->status) {
            $tasks['show'] = [
                0 => 'a',
                1 => \i('Not Spam'),
                2 => [
                    'href' => $link . $url->query('&', [
                        'name' => $name,
                        'parent' => false,
                        'task' => 'show'
                    ]),
                    'title' => \i('Mark this comment as not spam')
                ]
            ];
        } else {
            $tasks['hide'] = [
                0 => 'a',
                1 => \i('Spam'),
                2 => [
                    'href' => $link . $url->query('&', [
                        'name' => $name,
                        'parent'=> false,
                        'task' => 'hide'
                    ]),
                    'title' => \i('Mark this comment as spam')
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
                'href' => $link . $url->query('&', [
                    'name' => $name,
                    'parent' => false,
                    'task' => 'remove'
                ]),
                'title' => \i('Remove this comment')
            ]
        ];
        $tasks['delete'] = [
            0 => 'a',
            1 => \i('Delete'),
            2 => [
                'href' => $link . $url->query('&', [
                    'name' => $name,
                    'parent' => false,
                    'task' => 'delete'
                ]),
                'title' => \i('Delete this comment permanently')
            ]
        ];
    }
    return $tasks;
}

function comment_form($fields) {
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

function comment_form_tasks($tasks) {
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
                    'kick' => $url->path . $url->query . '#' . $comment_state->anchor[0]
                ])
            ]
        ])),
        2 => ['class' => 'button is:user']
    ];
    return $tasks;
}

\Hook::set('comment-form', __NAMESPACE__ . "\\comment_form", 10.1);
\Hook::set('comment-form-tasks', __NAMESPACE__ . "\\comment_form_tasks", 10.1);
\Hook::set('comment-tasks', __NAMESPACE__ . "\\comment_tasks", 10.1);