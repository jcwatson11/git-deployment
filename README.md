# fh/git-deployment

This package provides a hightly customizable PHP based deployment process intended to plug into a gitolite server-side post-recieve git hook. It integrates several strategies for dealing with common deployment tasks:

- Completely unit testable from beginning to end, including all behavior strategies before deployment. See tests directory for extensive unit tests.
- Auto-tagging of releases according to SemVer standards.
- Dealing with locally modified files in the target directory according to a given strategy.
- Post-deployment tasks, like auto-merging your deployed ref into another branch.
- Customizable strategies that you can modify and re-deploy easily.

## Pre-Requisites

This project assumes that you already have [gitolite](http://gitolite.com/gitolite/index.html) installed, and that you are somewhat familar with how to use a server-side git hook, specifically the post-receive hook. If your are not familiar with gitolite, please read their documentation and work through some of their examples. Set up a gitolite server for yourself and push to it from your repository to see how it works.

It is also helpful if you are familiar with [phar-composer](https://github.com/clue/phar-composer), and how phar files work in general. Though, this knowledge is not required.

## Process Overview

This project defines a PHP executable PHAR file that is spawned by the post-receive gitolite hook. An example post-receive hook file is defined below.

When your gitolite server receives a push to your project, the post-receive hook will execute with commit ID's and a human readable "ref" as parameters. The ref is something like refs/heads/branchname, or refs/tags/tagname.

The default deployment strategy will not deploy any ref that does not conform to SemVer naming standards. An example of a ref that does conform to SemVer naming standards would be something like: 3.0, or 3.0.1. If you would like to deploy a non-SemVer branch or tag name, you will need to create a new strategy. See [customizing](#markdown-header-customization-of-behaviors).

Once a push is received and the hook spawns git-deployment, this package goes to work by reading the environment variables for configuration details and target directory, and follows the configured strategies for deployment behavior.

All commands executed, and their output are echoed to standard out, which means when you do your push, the remote will respond with a print out of all deployment activity that is happening during deployment. So you can be immediately aware of how the deployment is going, and any problems the process might have encountered.

## Getting started with default features

It is recommended that you install the git-deployment.phar executable globally. There are two ways to do this:

### Quick start

NOTE: If you already have phar-composer installed, you can skip step 1:

1. Install phar-composer globally
```sh
$ wget http://www.lueck.tv/phar-composer/phar-composer.phar
$ chmod ugo+x phar-composer.phar
$ sudo mv phar-composer.phar /usr/local/bin/phar-composer
```
2. Install git-deployment globally
```sh
$ phar-composer build fh/git-deployment
$ chmod ugo+x git-deployment.phar
$ sudo mv git-deployment.phar /usr/local/bin/git-deployment
```
3. Configure your server-side gitolite post-receive hook

Below is an example of a post-receive hook that can be installed for a sample project.

```sh
#!/bin/bash

# FILE NAME: post-receive

DEPLOYMENT_LEVEL="beta"
DEPLOYMENT_TARGET_DIR="/var/www/www.domain.org"
DEPLOYMENT_REMOTE_NAME="beta"
GIT_DIR="/var/www/www.domain.org"
LOCALLY_MODIFIED_FILE_STRATEGY="Fh\\Git\\Deployment\\Strategies\\ResetLocallyModifiedFileStrategy"
DEPLOYMENT_STRATEGY="Fh\\Git\\Deployment\\Strategies\\DefaultDeploymentStrategy"
TAG_STRATEGY="Fh\\Git\\Deployment\\Strategies\\AutoIncrementTagStrategy"
POST_DEPLOYMENT_SCRIPT="./deploy.sh"
DEPLOYMENT_TESTING=1

dep=`which git-deployment`

while read oldrev newrev ref
do
    $dep $oldrev $newrev $ref
done
```

This file should be installed in your gitolite ~/repositories/project.git/hooks directory with an executable file mode (where project.git is the name of the git repository for your project). See the [gitolite documentation](http://gitolite.com/gitolite/index.html) for more information on gitolite. The file name should be "post-receive".

### Customization of behaviors

There are generally two paths you can take when customizing this package:

#### 1. Submit a pull request with a new strategy or bug-fix.

Bug-fixes to the existing strategy set are always welcome.

However, when submitting a new strategy, or modifying the behavior of an existing strategy, first ask yourself if this new strategy or behavior would be useful to most other deployment processes, or if this is a specific customization to your own process, consider forking instead.

#### 2. Fork this package to make your specific customizations.

If the strategy changes you need to make are specific to your deployment process. I would recommend making a fork of this package instead of submitting a pull request. This gives you the most freedom to make the changes you want while keeping this package as a pristine example of generally useful behavior.

##### Use of phar-composer

Understanding how to install this package with [phar-composer](https://github.com/clue/phar-composer) is useful for when you need to customize the default strategies for your particular deployment. This package cannot possibly anticipate all deployment needs. If you submit a new strategy or fork this package to create your own strategies, you will need to re-compile your git-deployment.phar executable and re-install it on your machine.

```sh
$ wget http://www.lueck.tv/phar-composer/phar-composer.phar
$ chmod ugo+x phar-composer.phar
$ sudo mv phar-composer.phar /usr/local/bin/phar-composer
```

Once you have phar-composer installed globally, you can create and install the git-deployment.phar package with:

```sh
$ phar-composer build fh/git-deployment
$ chmod ugo+x git-deployment.phar
$ sudo mv git-deployment.phar /usr/local/bin/git-deployment
```

NOTE: This works because phar-composer will check [packagist.org](http://packagist.org) for the fh/git-deployment project, and download it to a temporary directory and install it.

##### Installing your own fork

When forking this package, you may not be able to depend on packagist.org to install your modified fork. Private repositories cannot be registered with packagist.org. And a great deal of deployment work tends to be customized to a particular system.

Fortunately, the phar-composer program supports creating a new phar in very diverse ways. See their documentation for details. However, here is a quick example:

Let's say you forked this project into a directory called /home/me/projects/git-deployment.

Once you have made your changes, updated your unit tests and you're sure you're ready to try this out in a real deployment, simply use phar-composer to re-build your project and install it:

```sh
$ cd /home/me/projects
$ phar-composer build git-deployment/.
$ chmod ugo+x git-deployment.phar
$ sudo mv git-deployment.phar /usr/local/bin/git-deployment
```

## Bringing it all together

If you are making customizatoins regularly, once you understand how this process works, it makes sense to create another project with your post-receive hook file, and a Makefile for installation and deployment. That way you can make changes in both your git-deployment project and your post-receive hook and easily deploy them to your gitolite repository hooks directory for testing.

The fh/hook-example project provides an example of such a project. It is intended to be cloned by the gitolite user because he has the rights to copy files to the hooks directory for any given repository under his control. The work flow for such a process might be:

```sh
# Log in as the gitolite user
$ git clone git@bitbucket.org:/fhcode/hook-example.git
$ git clone git@bitbucket.org:/fhcode/fh-git-deployment.git
$ cd fh-git-deployment
$ # make customizations to your git-deployment strategies
$ cd ../hook-example
$ # make customizations to the Makefile and the post-receive hook for my project
$ make all
$ make install
```

This allows you quick, easy access to make changes, run unit tests, and re-deploy the git-deployment.phar and post-receive hook to your system with your recent customizations.

# Contributing

See [Customization of behaviors](#markdown-header-customization-of-behaviors) for guidance on deciding whether to contribute to this package or fork your own.

If you do decide to contribute to this package, please submit a pull request from a feature branch to the master branch.

# This project's branch and tag strategy

This project's branch stragegy is that master contains the latest development stream. Release branches are branched directly from master. Tags are tagged from their respective feature branches.

# Happy Coding!
