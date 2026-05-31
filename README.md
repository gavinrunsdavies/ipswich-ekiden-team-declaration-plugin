# Ipswich Ekiden Team Declaration API

A WordPress plugin that exposes REST API endpoints for managing Ipswich Ekiden teams, runners, clubs, and declarations.

## Overview

This plugin provides:

- public registration via `/wp-json/ipswich-ekiden-team-declaration-api/v1/users`
- authenticated team creation, updates and deletion
- runner management for team legs
- club and team statistics endpoints
- export/download of team declaration data

## Installation

1. Copy the plugin directory into `wp-content/plugins/ipswich-ekiden-team-declaration-plugin`.
2. Activate it from the WordPress admin Plugins screen.
3. The plugin will create its own database tables during activation.

## Activation

On activation, the plugin creates these tables using WordPress `dbDelta`:

- `wp_ietd_clubs`
- `wp_ietd_teams`
- `wp_ietd_runners`
- `wp_ietd_team_runners`

> If your database prefix is not `wp_`, WordPress will use your configured prefix automatically.

## REST API Endpoints

Base namespace: `/wp-json/ipswich-ekiden-team-declaration-api/v1`

### Public endpoints

- `POST /users`
  - Creates a new user.
  - Required fields: `email`, `password`, `firstName`, `lastName`

- `POST /message`
  - Sends a contact message.
  - Required fields: `email`, `firstName`, `lastName`, `message`

- `GET /clubs`
  - Returns club list.

- `GET /teams`
  - Returns all teams, optional query param: `race=seniors|juniors`

- `GET /statistics`
  - Returns team and runner statistics.

### Authenticated endpoints

- `GET /myteams`
  - Returns teams for the current logged-in user.

- `POST /teams`
  - Create a new team.
  - Required fields: `name`, `clubId`

- `PUT /teams/{id}`
  - Update an existing team.

- `DELETE /teams/{id}`
  - Delete a team.

- `POST /teams/{id}/runners/{leg}`
  - Add a runner for the requested leg.

- `PATCH /teams/{id}/runners/{leg}`
  - Update a runner field for the requested leg.
  - Supported fields: `name`, `gender`, `ageCategory`

- `GET /teams/download`
  - Returns CSV export data (editor/admin only).

- `POST /teams/send`
  - Sends exported teams to an email address (editor/admin only).

## Notes

- New users are created as WordPress `subscriber` users.
- The plugin now validates `email` and `password` during registration.
- The registration response no longer returns the user password.

## Development

- The main plugin bootstrap is `Program.php`.
- The REST API controller is `api/v1/class-ipswich-ekiden-team-declaration-api-controller-v1.php`.
- The data layer is `api/v1/class-ipswich-ekiden-team-declaration-data-access-v1.php`.

