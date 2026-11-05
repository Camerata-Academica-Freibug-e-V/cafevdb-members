<?php

use OCA\CAFeVDBMembers\Constants;

return [
  'routes' => [
    // Cannot be moved to attributes as the AuthPublicShareController implements this as "final".
    [
      'name' => 'ProjectRegistration#authenticate',
      'url' => '/registration/{token}/authenticate/{redirect}',
      'verb' => 'POST',
      'requirements' => [
        'token' => (
          '^'
          . Constants::TEMPORARY_PROJECT_NAME_REGEXP
          . '/'
          . Constants::PROJECT_APPLICATION_TOKEN_REGEXP
          . '$'
        ),
      ],
    ],
    // keep this for simplicity, otherwise we would have to copy the parent class method.
    [
      'name' => 'project_events_api#preflighted_cors',
      'url' => '/api/{apiVersion}/{path}',
      'verb' => 'OPTIONS',
      'requirements' => [
        'apiVersion' => '0.1',
        'path' => '.+',
      ],
    ],
  ],
];
