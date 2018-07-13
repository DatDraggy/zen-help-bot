[@ZenCashHelp_bot](https://t.me/ZenCashHelp_Bot) (Backup in case of rebranding [@ZenHelp_bot](https://t.me/ZenHelp_Bot) )

### ToDo List
- [ ] Clean up database queries (Ideas pls)
- [ ] How to mine
- [X] zengroups Languages
- [ ] /community: Discord twitter etc
- [ ] Improve /tip for better tipping (Main problem: unable to tip while balance being confirmed)

## Usage
#### Tipping
This bot enables users to send tips to users they find helpful.

To do this, the sending user firstly has to generate a deposit address by sending `/deposit` to the bot in private.
The user would then send ZEN to that address and wait until `/mybalance` shows the correct balance.

Once that balance has updated, the user can reply to the helpful message with `/tip` and the amount in ZEN behind it. (`/tip 0.01`)

If the user has enough funds, the bot will tell the helpful user in the group that they received a tip. If the funds are not enough (forgot to substract tip for example) nothing will happen.


A user that received a tip can either use that tip to tip other users, or withdraw their tip to their balance. 

This can be done with `/myaddress` and `/withdraw`. Firstly, the user has to set their receiving address by using `/myaddress t-addr`.

They can then use `/withdraw` with the amount following to withdraw their balance.


#### Donations
ZenCash zni7tRLevBnJxWMzkUoMVze1e6RCSPDdbfw

BTC 122WSgrn2YVG6KQSKB53jfCaZYi3xMi6nb

ETH 0x4b39187EBBb674Fb659A81a433D8e8AfbE3aA32b