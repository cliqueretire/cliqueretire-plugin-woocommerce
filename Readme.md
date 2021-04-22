# Deploy de novas releases

 - Incluir no diretório **/releases** um arquivo contendo o release notes com o nome da tag que definirá a nova versão. Ex: **/releases/1.0.x.md**
	 - o arquivo utiliza formatação de markdown
 - Criar uma TAG no repositório local e realizar o push:
	 `$ git tag -a 1.0.x -m "Versão 1.0.x"`
	 `$ git push origin 1.0.x`
- Após a criação da TAG o processo de publicação da release acontecerá de forma automática.

**Comandos auxiliares**  

 - Visualizar Tags localmente `$ git tag`
 - Exluir Tags localmente `$ git tag -d 1.0.x`
 - Excluir Tags Remotamente(repositório) `$git push --delete origin 1.0.x`
 - Alias para criar release
	 - incluir no .bashrc 
	 - `alias git-release='sh -c '\''git tag -a "$0" -m "Versão $0" && git push origin $0'\''' `
