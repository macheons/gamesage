# GameSage

> Un assistant conversationnel IA qui ne parle que d'une chose : **le jeu**. Expert des **mondes ouverts** et des **jeux de société**, GameSage répond avec la gouaille d'un vétéran qui a des décennies de bouteille derrière la manette et autour de la table.

GameSage est une application **ChatGPT-like** thématisée, construite autour d'une base de données soignée. Projet réalisé dans le cadre du cours **Projet de développement (SGBD)**.

---

## ✨ Fonctionnalités

- **💬 Chat multi-conversations** — historique persistant, navigation entre les discussions, et **titre généré automatiquement** au premier message.
- **⚡ Réponses en streaming** — les tokens s'affichent en temps réel via **Server-Sent Events**, pas d'un seul bloc.
- **🤖 Sélecteur de modèles** — bascule entre les LLM disponibles via OpenRouter ; le choix est **mémorisé par utilisateur**.
- **🎚️ Instructions personnalisées** — chaque utilisateur configure le ton et le contexte de l'IA (activable / désactivable), **injectés par-dessus** la personnalité GameSage.
- **🎭 Personnalité thématisée** — GameSage refuse poliment le hors-sujet et les jeux purement PvP, et recentre la discussion vers les mondes ouverts et les jeux de société.

---

## 🛠️ Stack technique

| Couche | Technologie |
|---|---|
| Backend | Laravel |
| Pont front / back | Inertia.js |
| Frontend | Vue 3 (Composition API) |
| Styles | TailwindCSS + shadcn-vue |
| Base de données | SQLite |
| Authentification | Laravel Fortify |
| LLM | API OpenRouter |

---

## 🗄️ Schéma de la base de données

Le cœur du projet. Quatre tables reliées par des clés étrangères avec intégrité référentielle (`ON DELETE CASCADE`) :

- **`users`** — les utilisateurs (+ colonne `preferred_model` pour mémoriser le dernier modèle choisi).
- **`conversations`** — appartient à un `user` (relation 1-N). Contient `title` (généré automatiquement) et `model`.
- **`messages`** — appartient à une `conversation` (relation 1-N). `role` (`user` / `assistant`) + `content`.
- **`custom_instructions`** — relation **1-1** avec `user` (clé `user_id` unique). `about_you`, `behavior`, `is_enabled`.

```
users ──1:N──▶ conversations ──1:N──▶ messages
  └────1:1────▶ custom_instructions
```

Supprimer un utilisateur supprime en cascade ses conversations, ses messages et ses instructions (intégrité garantie au niveau de la base).

---

## 🚀 Installation

Prérequis : **PHP 8.3+**, **Composer**, **Node.js**, et une clé API [OpenRouter](https://openrouter.ai).

```bash
# 1. Cloner le dépôt
git clone https://github.com/macheons/gamesage.git
cd gamesage

# 2. Installer les dépendances
composer install
npm install

# 3. Configuration
cp .env.example .env
php artisan key:generate
```

Renseigne ensuite dans le fichier `.env` :

```
APP_NAME="GameSage"
OPENROUTER_API_KEY=ta_cle_openrouter
```

```bash
# 4. Base de données (SQLite)
php artisan migrate

# 5. Lancer le projet (dans deux terminaux séparés)
php artisan serve   # application  -> http://localhost:8000
npm run dev         # compilation des assets (Vite)
```

Ouvre **http://localhost:8000**, crée un compte, puis rends-toi sur **`/chat`**.

---

## 📁 Points d'architecture

- `app/Services/SimpleAskService.php` — appels synchrones à OpenRouter (génération du titre, réponses simples).
- `app/Services/SimpleAskStreamService.php` — streaming SSE des réponses, token par token.
- `app/Http/Controllers/ChatController.php` — liste des conversations, persistance des messages, endpoint de streaming.
- `resources/views/prompts/system.blade.php` — prompt système définissant la personnalité de GameSage.
- `resources/js/pages/Chat/Index.vue` — interface de chat (Vue 3, Composition API).

---