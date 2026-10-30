# ADR 0002: Identity and Single Sign-On (SSO)

- **Status:** Accepted
- **Track:** Working code

## Context

The client is a large German corporation with strict corporate IT security standards. Enterprise employees (Project Leads, Purchasing Officers, and Finance Controllers) sign in using their corporate Identity Provider (IdP) such as Microsoft Entra ID (Azure AD), Okta, or on-premise Active Directory Federation Services (AD FS) over SAML 2.0 or OpenID Connect (OIDC).

Simultaneously, external consultants belong to third-party supplier companies (e.g. Nova Consulting GmbH). Forcing external consultants onto the internal corporate directory is either legally problematic (Scheinselbstständigkeit risks) or IT-prohibitive.

During the hackathon evaluation, the technical jury must be able to test and run the application within 5 minutes without needing access to a corporate cloud tenant or real external SAML credentials.

## Decision

- **In the Hackathon Demo:** We provide pre-seeded demo accounts representing the four core roles (`consultant@pegel.test`, `lead@pegel.test`, `purchasing@pegel.test`, `finance@pegel.test`) with a one-click role-switcher in the application header or local login screen. Passwords are safe defaults (`password`).
- **In Production Architecture:**
  1. **Dual-Federation Identity Gateway:**
     - Client company users authenticate via OpenID Connect (OIDC) / SAML 2.0 bridged through Laravel Socialite / Passport connected to the client's corporate IdP.
     - External service provider consultants authenticate either via their own supplier SAML/OIDC federations or through a dedicated guest B2B identity pool with multi-factor authentication (MFA / TOTP).
  2. **Role Mapping & Just-In-Time (JIT) Provisioning:**
     - Corporate SAML claims and group memberships (e.g., `CN=Purchasing_Managers,OU=Groups`) are mapped to Pegel application roles via configurable environment rules.
     - Personal identifiable information (PII) is minimized during token exchange (email, display name, user ID).

## Alternatives considered

- **Requiring real Microsoft Entra / Auth0 API keys in demo:** Rejected. Criterion 5 explicitly mandates that no paid cloud account, external tenant, or external API key may be required to run the application from the README.
- **Single monolithic LDAP directory for both client and suppliers:** Rejected. Enterprise security policies strictly prohibit provisioning external agency workers directly into internal directory groups without complex guest onboarding delays.
- **Pure basic HTTP auth:** Rejected. Fails to demonstrate realistic enterprise scalability and cannot accommodate future SSO token refreshes.

## Consequences

- The demo runs instantly in offline/local environments without network dependencies.
- Clear technical roadmap for enterprise production integration via SAML 2.0 / OIDC is established.
- Role-based authorization policies (`Gate` / `Policy` in Laravel) operate uniformly on the resolved `User::$role` attribute, regardless of whether the identity was verified via local credentials or an enterprise OIDC token.

