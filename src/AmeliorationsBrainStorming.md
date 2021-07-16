# Possibles améliorations du design de l'application
## Introduction
Pour récupérer des données, un format court d'URL est utilisé. Cela peut être
pratique pour interroger des données mais ça l'est moins lorsqu'il faut
restreindre les accès. Prenons l'exemple des projets :
En ce moment, la liste des projets sont récupérés en attaquant l'endpoint
"/projects", cependant il est évident que n'importe qui interrogeant cette
terminaison ne devrait pas avoir accès à l'ensemble des projets de
l'application.
 
Pourtant, certains me diront "Mais pourquoi ne pas simplement créer une règle de sécurité
avec le système de Voter de Symfony ?". Le problème est qu'une réponse 403
indique quand même à l'utilisateur que la ressource existe... ce qui est déjà
une faille de sécurité en soi. On pourrait un peu résoudre ce problème en
utilisant des UUID mais mon avis est que ceux-ci ne sont pas destinées à cet
usage n'en déplaise à plusieurs :
 
  1. Il est impossible de les retenir de tête en phase de développement
  2. Ils prennent beaucoup de place en base de données (bien que de nos jours
     l'espace disque soit disponible à foison...).
  3. Ils rallongent l'URL la rendant moins Human-Friendly.
  4. Lorsqu'ils sont utilisés en tant que clef primaire, il est impossible de trier les données par ordre d'insertion sans ajouter
     un autre champ "created_at".
     
À par pour des "Bulk Insert" ainsi qu'une génération de l'identifiant côté
client, ils ne résolvent pas grand-chose de mon avis.

Pour poursuivre après cette parenthèse critique sur les UUIDs, ils résolvent
cependant pas complétement le problème bien que ce serait à moitié une solution
en soi. Si quelqu'un enregistrait les UUIDs consultés, ils pourraient
tout de même révéler la présence d'une donnée si une réponse "403" est levée.

## Première piste
Pour résoudre ce problème, il est alors nécessaire de créer une
extension de Doctrine (ORM) afin d'ajouter une condition "WHERE" pour ne
récupérer que les projets de l'utilisateur connecté.

Cette solution fonctionne correctement mais elle a ses limites :

 1. Lorsque l'application se complexifie, ces extensions deviennent difficiles à
    écrire et à tester.
 2. L'API n'est plus "stateless", elle dépend désormais de l'état de
    l'utilisateur courant. C'est-à-dire que chaque utilisateur obtiendra une 
    réponse différente. De ce fait, impossible de mettre en cache la
    réponse pour cette route-là (sauf erreur de ma part) ! 
 3. L'application est difficilement maintenable par quelqu'un qui ne connaît que
    sommairement l'ORM Doctrine.
 4. Pour chaque extension, il faut binder les paramètres de l'extension au sein
    du fichier services.yaml. Ce qui le "pollue" rapidement.

De ce fait, on découvre qu'API Platform devient rapidement complexe et que plus
on veut faire dans la dentelle, plus tout devient compliqué.

## Deuxième piste
Comme amélioration possible, ils seraient plus élégants d'utiliser un système
avec des URL différentes. Reprenons l'exemple des projets :
Au lieu d'avoir la liste des projets sur "/projets" avec une extension pour
avoir les projets de l'utilisateur courant, imaginons une API RESTFull où les 
utilisateurs seraient identifiés au sein de l'URL avec leur nom d'utilisateur :

 - Pour récupérer les informations de John Doe : GET "/users/johndoe"
 - Pour récupérer les projets de John Doe : GET "/users/johndoe/projects"
 
N.B : Le système fonctionnerait à merveille cependant il imposerait de
      restreindre les caractères utilisables pour les noms d'utilisateurs à
      cause de leur intégration dans l'URL. De plus cette solution poserait
      problème au niveau des logs car on saurait directement qu'est-ce qui
      seraient le plus interrogé avec l'username dans l'url.
 
 - Pour que l'administrateur récupèrent tous les projets : GET "/projects"
 - Pour récupérer les entités du projet "Test" de John : 
   GET "/users/john/projects/test/entities"
   ou "/users/12231/projects/95923/entities"
   
À nouveau, il faudrait restreindre le nom des projets pour autoriser leur
utilisation dans l'URL. <br>
Mise à part ceci, on voit que l'éventuelle
extension que l'on aurait codé dans le cas GET "/entities" serait
difficile à créer contrairement à la solution proposée ci-dessus car
l'URL contiendrait directement de quoi identifier le projet c'est-à-dire
l'identifiant du créateur ainsi que l'identifiant du projet.
Il suffirait alors de parser l'URL de gauche à droite pour vérifier sa
validité :
      
 1. John (12231) existe-il ? --> Si non, ERREUR !
 2. Le projet "test" (95923) existe-t-il ? --> Si non, ERREUR !
 
Certains me répondront alors qu'il suffirait d'utiliser le système de 
subresource d'API Platform pour mon cas. Je l'ai déjà essayé mais cela n'a pas 
abouti pour des problèmes de sécurité, de documentation générée... Du coup, pour
ceux qui veulent refaire l'app sur une autre techno, à vos claviers !!!!

## Quelques idées...
On pourrait imaginer des systèmes de route dynamiques :
```
/users : {
  route "/" : y a-t-il une suite à URL ? Si oui poursuivre les routes : {
    {slug ou id} : parser, vérifier l'existence de la donnée et ainsi de suite.
  },
  sinon vérifier la méthode & faire les actions nécessaires
  GET
  POST
  PATCH
  ...
}
```

De plus, il serait préférable d'utiliser autre chose dans l'URL que les ID de
Base de données, car si l'on fait une migration (sans préserver les id),
tous les internautes qui auraient mis certaines URL dans leurs favoris 
tomberaient sur des erreurs 404 ! Peut-être que les UUID ou une alternative plus
légère serait une solution...
      
     
