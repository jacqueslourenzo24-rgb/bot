# Usa uma imagem base oficial do PHP com Apache.
# '8.2-apache' pode ser trocado pela versão do PHP que você prefere (ex: 8.1-apache, 8.3-apache)
FROM php:8.2-apache

# Define o diretório de trabalho dentro do contêiner
WORKDIR /var/www/html

# Copia todos os arquivos do seu projeto (onde o Dockerfile está) para o diretório de trabalho do contêiner
COPY . .

# Instala ferramentas necessárias e o Composer
# 'unzip' é necessário para Composer, 'git' para dependências do Composer.
# Baixa e instala o Composer no /usr/local/bin, tornando-o globalmente disponível.
RUN apt-get update && apt-get install -y \
    unzip \
    git \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && rm -rf /var/lib/apt/lists/*

# Instala as dependências do Composer.
# '--no-dev' para não instalar dependências de desenvolvimento em produção.
# '--optimize-autoloader' para otimizar o autoloader do Composer.
RUN composer install --no-dev --optimize-autoloader

# Ativa o módulo 'rewrite' do Apache (muitas vezes necessário para URLs amigáveis, embora seu webhook.php não precise diretamente)
RUN a2enmod rewrite

# Expõe a porta 80, que é a porta padrão do Apache.
# A Render irá mapear isso para a $PORT ambiente.
EXPOSE 80

# Comando para iniciar o servidor Apache quando o contêiner for executado.
# Como usamos uma imagem base Apache, ele já tem um comando padrão.
# Isso garante que o Apache esteja servindo os arquivos do /var/www/html
CMD ["apache2-foreground"]
