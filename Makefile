#!/usr/bin/make -f

.PHONY: tests

# ------------------------------------------------------------------------------

all: tests

tests: .drone
	@drone exec

.drone:
	@echo ">>> Downloading Drone for MAC ..."
	@curl -L https://github.com/drone/drone-cli/releases/download/v0.7.0/drone_darwin_amd64.tar.gz | tar zx
	@chmod +x drone
	@./drone -v
	@mv drone .drone
