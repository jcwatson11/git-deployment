<?php

return [
    'level' => getenv('DEPLOYMENT_LEVEL')
    ,'target' => getenv('DEPLOYMENT_TARGET_DIR')
    ,'deployment_remote_name' => getenv('DEPLOYMENT_REMOTE_NAME')
    ,'git_dir' => getenv('GIT_DIR')
    ,'git_work_tree' => getenv('GIT_DIR')
    ,'gitsetup' => "--git-dir " .getenv('GIT_DIR'). "/.git --work-tree " .getenv('GIT_DIR'). "/."
    ,'locally_modified_file_strategy' => getenv('LOCALLY_MODIFIED_FILE_STRATEGY')
    ,'tag_strategy' => getenv('TAG_STRATEGY')
    ,'deployment_strategy' => getenv('DEPLOYMENT_STRATEGY')
    ,'pre_deployment_script' => getenv('PRE_DEPLOYMENT_SCRIPT')
    ,'post_deployment_script' => getenv('POST_DEPLOYMENT_SCRIPT')
    ,'testing' => getenv('DEPLOYMENT_TESTING')
];
