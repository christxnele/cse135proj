[README.md](https://github.com/user-attachments/files/24816275/README.md)
## Names of all members in our team
- Victoria Timofeev
- Christine Le
- Ryan Soe

## The password for user "grader" on your Apache server

ssh grader@142.93.88.250

password: grader

## Link to our site, which has:
https://cse135vrc.site

- homepage with team member info and homework links
- about pages for each team member
- favicon
- robots.txt
- hw1/hello.php
- hw1/report.html

## Details of Github auto deploy setup
This project uses a simple GitHub Actions–based deployment pipeline that automatically deploys the site to a DigitalOcean server on every push to the main branch using SSH and rsync. The repository is organized into separate directories for each site, and a GitHub Actions workflow (.github/workflows/deploy.yml) checks out the code, configures SSH using a private key stored in GitHub Secrets, and synchronizes each directory to its corresponding web root under /var/www on the server. On the server side, a dedicated deploy user is created, granted access to the web directories, and configured with an SSH key whose private portion is added to GitHub Secrets along with the server IP and username. This setup enables fast, repeatable deployments using only native tools (SSH and rsync), avoids third-party deployment services, and keeps full control of the infrastructure, with an optional optimization to deploy only the sites whose files changed.

## Username/password info for logging into the site
Username: ryan  
Password: ryan

Username: victoria  
Password: victoria

Username: christine  
Password: christine

Username: grader  
Password: grader

## 3rd party analytics discussion
We chose Plausible Analytics, because it emphasizes privacy with aggregate metrics rather than individual user tracking. The setup process was simple and appears to work well with low traffic.
Pageview and visitor data appeared in the Plausible dashboard shortly after generating traffic,
unlike with Google Analytics which required time to load data. Plausible had a clean overview of traffic sources, pages visited, and overall usage without extra detail.

## Summary of changes to HTML file in DevTools after compression
After implementing compressions to our site, we observed that the file size for our main page got compressed from 0.6 kB to 0.5 kB.

## Summary of removing 'server' header

First, I ran this:
```
sudo a2enmod headers
sudo systemctl restart apache2
```
This is to install mod_headers which is used to change the server header.

Then, I added this to the apache config file:
```
<IfModule mod_headers.c>
    Header always set Server "CSE135 Server"
    Header unset X-Powered-By
</IfModule>
```
This ensures that our custom server header always overrides the Apache default server header.
