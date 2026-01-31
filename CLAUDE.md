# Yelira - Mode Modeste E-commerce

## Description
Boutique e-commerce de mode modeste pour femmes musulmanes. Design inspiré de Neyssa Shop.

**Site :** https://www.yelira.fr
**GitHub :** https://github.com/Derkash/yelira-child-theme

## Stack Technique
- **CMS :** WordPress 6.9
- **E-commerce :** WooCommerce
- **Thème parent :** Blocksy
- **Thème enfant :** yelira-child-theme (ce repo)
- **Hébergement :** O2switch

## Credentials API

### WooCommerce REST API v3
```
Consumer Key: ck_fda27c64ff65524550b3e0e6c59c1e3f0a7580a3
Consumer Secret: cs_cb25ef00fb0afb0826ce8ea0258b70f86ef12f6b
```

Exemple d'utilisation :
```bash
curl -s "https://www.yelira.fr/wp-json/wc/v3/products" \
  -u "ck_fda27c64ff65524550b3e0e6c59c1e3f0a7580a3:cs_cb25ef00fb0afb0826ce8ea0258b70f86ef12f6b"
```

### WordPress REST API v2
```
Username: admin_yelira
App Password: pQ6A cCtF MqRB NkgJ SDF3 cugh
```

Exemple d'utilisation :
```bash
curl -s "https://www.yelira.fr/wp-json/wp/v2/pages" \
  -u "admin_yelira:pQ6A cCtF MqRB NkgJ SDF3 cugh"
```

## Déploiement

### Auto-deploy via GitHub Webhook
Le site se met à jour automatiquement quand on push sur `main`.

**Webhook URL :** https://www.yelira.fr/deploy.php
**Secret :** yelira_deploy_2026

### Workflow
1. Modifier les fichiers localement
2. `git add . && git commit -m "message" && git push`
3. Le webhook déclenche `git pull` sur le serveur

## Structure du Thème

```
yelira-child-theme/
├── style.css          # CSS principal (design Neyssa Shop)
├── functions.php      # PHP (header, footer, WooCommerce hooks)
├── assets/
│   └── js/
│       └── main.js    # JavaScript (menu, recherche, etc.)
└── CLAUDE.md          # Ce fichier
```

## Design

### Palette de couleurs
- Noir : `#000000`
- Blanc : `#ffffff`
- Taupe : `#997a6e`
- Beige : `#f5f1eb`
- Rouge (soldes) : `#c41e3a`

### Typographie
- Titres : Cormorant Garamond
- Corps : Inter

## Catégories Produits (IDs)

| Catégorie | ID |
|-----------|-----|
| Abayas | 43 |
| Hijabs | 44 |
| Jilbabs | 45 |
| Burkini | 46 |
| Robes | 47 |
| Khimar | 48 |
| Prière | 50 |
| Nouveautés | 51 |
| Soldes | 52 |
| Prêt-à-porter | 53 |
| Homme | 55 |
| Enfant | 56 |

## Commandes Utiles

### Créer un produit
```bash
curl -X POST "https://www.yelira.fr/wp-json/wc/v3/products" \
  -u "ck_fda27c64ff65524550b3e0e6c59c1e3f0a7580a3:cs_cb25ef00fb0afb0826ce8ea0258b70f86ef12f6b" \
  -H "Content-Type: application/json" \
  -d '{"name": "Nom produit", "regular_price": "29.90", "categories": [{"id": 43}]}'
```

### Créer une page
```bash
curl -X POST "https://www.yelira.fr/wp-json/wp/v2/pages" \
  -u "admin_yelira:pQ6A cCtF MqRB NkgJ SDF3 cugh" \
  -H "Content-Type: application/json" \
  -d '{"title": "Titre", "content": "<p>Contenu</p>", "status": "publish"}'
```

### Lister les produits
```bash
curl -s "https://www.yelira.fr/wp-json/wc/v3/products?per_page=100" \
  -u "ck_fda27c64ff65524550b3e0e6c59c1e3f0a7580a3:cs_cb25ef00fb0afb0826ce8ea0258b70f86ef12f6b" | jq
```

## Notes

- **SSH bloqué** : Le FAI bloque le port 22, utiliser les APIs à la place
- **Permaliens** : Doivent être en "Nom de l'article" pour que l'API fonctionne
- **Cache** : Vider le cache après modifications si nécessaire

## Tâches Futures

- [ ] Ajouter images aux produits
- [ ] Configurer les moyens de paiement (Stripe, PayPal)
- [ ] Configurer la livraison (Colissimo, Mondial Relay)
- [ ] SEO et meta descriptions
- [ ] Newsletter (Mailchimp ou autre)
