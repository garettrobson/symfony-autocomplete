Installation for testing

* `Install composer` & `composer install` - get the repositories vendor folder is setup
* `sudo ln -s /path/to/symfony-autocomplete.sh /usr/local/bin/symfony-autocomplete` - set up the command, anywhere available to PATH should work.
* `sudo ln -s /path/to/resources/00-symfony-completer-complete /etc/bash_completion.d/00-symfony-completer-complete` - Setup the bash script that supplies the functions bash's autocomplete will call.
* `sudo ln -s /path/to/resources/symfony-completer-composer /etc/bash_completion.d/symfony-completer-composer` - Setup a bash script tells bash autocomplete to use our function with composer. This is the prototype file to implement autocompletion on symfony based commands.

You now need to start a new shell by running `bash` so all that stuff gets fired.
