# Projet Le Blog de Batman

### Cloner le projet

````
git clone https://github.com/Naninha17/leblogdebatman.git
````

### Déplacer le terminal dans le dossier cloné
```
cd leblogdebatman
```

### Installer les vendors (pour recréer le dossier vendor)
```
composer install
```

### Création base de données 
Configurer la connexion à la base de données dans le fichier .env (voir cours), puis taper les commandes suivantes :
```
symfony console doctrine:database:create
symfony console doctrine:migrations:migrate
```

### Création des fixtures
````
symfony console doctrine:fixtures:load
````
Le commande fixtures créera : 
* Un compte admin (email : a@a.a , password : 'Azerty12!')
* 10 comptes utilisateur (email aléatoire , password : 'Azerty12!')

### Installation fichier front-end bundles (CKEditor)
````
symfony console assets:install public
````

### Lancer le serveur
```
symfony serve
```