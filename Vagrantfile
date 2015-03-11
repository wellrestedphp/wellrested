port = ENV["HOST_PORT"] || 8080

Vagrant.configure("2") do |config|
  # Ubuntu 14.04 LTS
  config.vm.box = "ubuntu/trusty64"
  config.vm.network "forwarded_port", guest: 80, host: port
  config.vm.provision "shell", path: "vagrant/provision.sh"
end
