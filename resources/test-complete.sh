#! /bin/bash
__symfony_completer_complete()
{
    local cur
    _get_comp_words_by_ref -n : cur
    words=(symfony-completer complete ${COMP_WORDS[@]})
    if [ $RESULT -eq 0 ] then
        mapfile -t COMPREPLY < <(echo $words)
    else
        COMPREPLY=($(compgen -o filenames -A file $cur))
    fi
    __ltrim_colon_completions "$cur"
    return 0
}
complete -F __symfony_completer_complete composer
