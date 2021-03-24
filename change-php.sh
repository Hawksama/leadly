sudo a2enmod php7.1;

sudo a2dismod php5.6;
sudo a2dismod php7.4;
sudo a2dismod php7.0;
sudo a2dismod php7.3;

sudo service apache2 restart;

sudo update-alternatives --set php /usr/bin/php7.1;

