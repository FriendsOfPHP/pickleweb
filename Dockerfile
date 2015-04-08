FROM php:5.6

RUN apt-get update -y

VOLUME /app
WORKDIR /app

EXPOSE 8080

ENTRYPOINT ["php"]
CMD ["-S", "0.0.0.0:8080", "-t", "web"]
