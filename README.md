# Duolingo vocabulary to CSV

Disclaimer: this project is not linked/supported by Duolingo

## Install 

If you know PHP you can install this code on your machine with php:  
- Clone this repo 
- Run `composer install`
- Update the code if needed
- Run the command via `php -f duo.php [parameters] > output.csv`

## Docker

If you prefer you can use the docker image published at: `jrmgx/duo-vocabulary`.

The full command will be `docker run jrmgx/duo-vocabulary [parameters] > output.csv`

## Usage

Launch the command via php or docker and use these parameters:

    --login     Duolingo email
    --password  Duolingo password
    --learning  Language you are learning 
                Short language code: en for english, es for spanish, etc.
    --native    Your native language
                Short language code: en for english, es for spanish, etc.

All parameters are mandatory

ex: `docker run jrmgx/duo-vocabulary --login jerome@domain.net --password 'myP@ssword!' --learning es --native fr`

## Specific 

This code has been made to export the vocabulary learnt in Duolingo to a Flash card app called: Ankiapp that accept CSV as input deck.

It also has some specific hardcoded rules to remove feminines and plurals words, but those rules only work for Spanish (the language I'm learning right now).
You can have a look at the source code to adapt or remove those. I'm pretty sure they won't be blockers for other languages.

## Learn More

Learn more about it on my blog: [jerome.gangneux.net/2022/07/03/duolingo-to-flashcards](https://jerome.gangneux.net/2022/07/03/duolingo-to-flashcards/)

This project is based on previous work by [@KartikTalwar](https://github.com/KartikTalwar) (see [github.com/KartikTalwar/Duolingo](https://github.com/KartikTalwar/Duolingo))

## Limitations

Duolingo does not provide an official API, because of that, it may stop working at anytime (without any way to fix it).

Know issue: call to `dictionary/hints` are limited in payload size, it has to be split at some point, make a PR if you want to help :)
