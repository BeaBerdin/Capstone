# PATHWISE possible future items

Items discussed but deliberately **not built**. Nothing here is implemented.

## EDP anonymization of an account (account deletion option 2)

- **Current decision:** option 1. Users can only deactivate their own account;
  requests for permanent deletion go to the EDP office, who keep the account
  deactivated. Accounts are never deleted, so academic and financial records
  are kept.
- **Possible future option:** a Super Admin action that anonymizes an account
  instead of deleting it:
  - replace the name and email with placeholders (for example "Deleted user");
  - remove the password, passkeys, two-factor secrets and remember token;
  - keep the account deactivated;
  - keep every enrollment, payment, submission, grade and certificate.
- **Why it might be needed:** a clear way to honour a request for erasure under
  the Data Privacy Act of 2012 (RA 10173) while keeping required records.
- **Open question before building it:** should **issued certificates keep the
  student's name** (on the certificate PDF and the public verification page),
  or show the anonymized name? Keeping the name preserves the academic record;
  removing it is more complete anonymization.
- Not chosen: a full request → EDP review → anonymize workflow with an audit
  trail (option 3). If it is ever built, one EDP approval with a required reason
  and an audit log entry is enough; a two-person rule only makes sense with
  several Super Admins.
