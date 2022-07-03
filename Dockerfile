FROM php:8.1-cli

RUN mkdir /app
COPY vendor /app/vendor/
COPY composer.json composer.lock duo.php /app/
ENTRYPOINT ["php", "-f", "/app/duo.php", "--"]
CMD ["php", "-f", "/app/duo.php", "--"]
