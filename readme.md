# Symfony Completer
`symfony-completer []`

## Requirements

Symfony Completer requires several packages to be fully supported
* `bash` - Shell interpreter.
* `php-cli` - To run the php script.
* `bash-completion` - Handles common bindings for tab-completion and helper functions.
* `composer` - PHP package manager.
* `sudo` - For some installation options.

Each can typically be installed by a variety of method but most of the testing has been done on Debian & Ubuntu using `apt` with success.

## Installing the project

To start with we need to install the tool before we can configure its use.

Change directory to the directory which should include _symfony-autocomplete_
```SHELL
cd /path/to/install/to
```

Clone the repo, by default this will create a populate _./symfony-autocomplete_
```SHELL
git clone git@github.com:garettrobson/symfony-autocomplete.git
```

Change directory to the newly create repo, if you chose to clone to a different directly please substitute with the appropriate directory name.
```SHELL
cd symfony-autocomplete
```

Installs the PHP dependencies for the project, add the `--no-dev` to exclude development tools for testing, which will make the installation smaller.
```SHELL
composer install
```

## Ephemeral installation (For testing and demonstration)

We can now make the `symfony-completer.sh` available to your user by symlinking it to the an any `PATH` location where commands will be looked up, in this example we use `/usr/local/bin` but any `PATH` accessible location will do.

Make the `symfony-completer.sh` available under the name `symfony-completer`, this will allow natural tab-completion on the commands name.
```SHELL
ln -s /path/to/install/to/symfony-completer/symfony-completer.sh /usr/local/bin/symfony-completer
```

Now we can load the helper bash helper function which will handle throughput of tab-completion events, and also register the `symfony-complete` command to use that function.
```SHELL
source /path/to/install/to/symfony-completer/resources/00-symfony-completer-complete
```

We can now test this works.

```SHELL
symfony-complete<tab><tab>
```

## Persistent installation (For global use)

For a persistent installation symlink the `/path/to/install/to/symfony-completer/resources/00-symfony-completer-complete` file into `/etc/bash_completion.d/`, you can either do this manually or by using `symfony-completer`'s own `install` command.

```SHELL
symfony-completer install [types-options] [action-options] [--exec]
```

Type Options:
* `--app` - `symfony-completer.sh` linking to  `/usr/local/bin`
* `--script` - `00-symfony-completer-complete` and `symfony-completer-composer` linking to `/etc/bash_completion.d/`

Action Options
* `--status` - Display information about any links type selected.
* `--purge` - Purge (`rm`) any link types selected.
* `--link` - Link (`ln -s`) any link type selected.

Execution
* `--exec` - Executes the shell script commands generated by actions.

A typical persistent install would look something like this...
```SHELL
symfony-completer install --app --script --link
```

Which should output
```
Gathering links
 +app
 +script

Commands to perfrom
 sudo ln -s /path/to/install/to/symfony-completer/symfony-completer.sh /usr/local/bin/symfony-completer
 sudo ln -s /path/to/install/to/symfony-completer/resources/00-symfony-completer-complete /etc/bash_completion.d/00-symfony-completer-complete
 sudo ln -s /path/to/install/to/symfony-completer/resources/symfony-completer-composer /etc/bash_completion.d/symfony-completer-composer
```

You can either use the shell commands listed under *Commands to perfrom* manually to suit your needs or add the `--exec` option to run them automatically. NOTE: These commands use `sudo` and as such you will be prompted for your password.

## Working Notes

Used the following to setup my user to have exclusive use of it by adding `/home/{user}/bin` to `$PATH`.
```
mkdir ~/bin
ln -s /path/to/symfony-autocomplete/symfony-completer.sh ~/bin/symfony-completer
```

Then in `.bashrc` ...
```
PATH=$PATH:~/bin

. /path/to/symfony-autocomplete/resources/00-symfony-completer-complete
. /path/to/symfony-autocomplete/resources/symfony-completer-composer
. /path/to/symfony-autocomplete/resources/symfony-completer-test
```
