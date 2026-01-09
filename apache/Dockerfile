FROM httpd:2.4

# 移除預設 htdocs 站台
RUN sed -i '/DocumentRoot "\/usr\/local\/apache2\/htdocs"/d' /usr/local/apache2/conf/httpd.conf \
 && sed -i '/<Directory "\/usr\/local\/apache2\/htdocs">/,/<\/Directory>/d' /usr/local/apache2/conf/httpd.conf

# 啟用必要模組
RUN sed -i 's/#LoadModule proxy_module/LoadModule proxy_module/' /usr/local/apache2/conf/httpd.conf \
 && sed -i 's/#LoadModule proxy_fcgi_module/LoadModule proxy_fcgi_module/' /usr/local/apache2/conf/httpd.conf \
 && sed -i 's/#LoadModule rewrite_module/LoadModule rewrite_module/' /usr/local/apache2/conf/httpd.conf

# 載入 conf.d
RUN echo "Include conf/conf.d/*.conf" >> /usr/local/apache2/conf/httpd.conf