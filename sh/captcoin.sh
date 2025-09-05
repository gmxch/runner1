#!/bin/bash
ACCOUNTS=$1
cat > generated_config.yml <<'EOF'
version: 2.1

jobs:
  run-bot:
    parameters:
      account:
        type: string
    docker:
      - image: cimg/php:8.2
    steps:
      - checkout
      - run:
          name: Run Captcoin
          command: |
            echo "Running bot for account << parameters.account >>"
            export USERNAME="gtg<< parameters.account >>"
            export PASSWORD="memeX3213!"
            php captcoin/captcoin.php

workflows:
  run-bots:
    jobs:
EOF

for acct in $(echo "$ACCOUNTS" | tr "," "\n"); do
  echo "      - run-bot:" >> generated_config.yml
  echo "          account: \"$acct\"" >> generated_config.yml
done
