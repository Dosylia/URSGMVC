class Profile {
    constructor(
        userid,
        gender,
        age,
        gamerkind,
        game,
        serverLol,
        mainLol1,
        mainLol2,
        mainLol3,
        rankLol,
        roleLol,
        serverValo,
        mainValo1,
        mainValo2,
        mainValo3,
        rankValo,
        roleValo,
        lookingGender,
        lookingGamerkind,
        lookingGame,
        lookingMainLol1,
        lookingMainLol2,
        lookingMainLol3,
        lookingRankLol,
        lookingRoleLol,
        lookingMainValo1,
        lookingMainValo2,
        lookingMainValo3,
        lookingRankValo,
        lookingRoleValo,

    ) {
        this.userid = userid;
        this.gender = gender;
        this.age = age;
        this.gamerkind = gamerkind;
        this.game = game;
        this.serverLol = serverLol;
        this.mainLol1 = mainLol1;
        this.mainLol2 = mainLol2;
        this.mainLol3 = mainLol3;
        this.rankLol = rankLol;
        this.roleLol = roleLol;
        this.serverValo = serverValo;
        this.mainValo1 = mainValo1;
        this.mainValo2 = mainValo2;
        this.mainValo3 = mainValo3;
        this.rankValo = rankValo;
        this.roleValo = roleValo;
        this.lookingGender = lookingGender;
        this.lookingGamerkind = lookingGamerkind;
        this.lookingGame = lookingGame;
        this.lookingMainLol1 = lookingMainLol1;
        this.lookingMainLol2 = lookingMainLol2;
        this.lookingMainLol3 = lookingMainLol3;
        this.lookingRankLol = lookingRankLol;
        this.lookingRoleLol = lookingRoleLol;
        this.lookingMainValo1 = lookingMainValo1;
        this.lookingMainValo2 = lookingMainValo2;
        this.lookingMainValo3 = lookingMainValo3;
        this.lookingRankValo = lookingRankValo;
        this.lookingRoleValo = lookingRoleValo;
    }
}

class Champion {
    constructor(name, lane, championtype, damagetype, speciality) {
        this.name = name;
        this.lane = lane;
        this.championtype = championtype;
        this.damagetype = damagetype;
        this.speciality = speciality;
    }
}

class Champion_Valo {
    constructor(name, champion_class) {
        this.name = name;
        this.champion_class = champion_class;
    }

}

//Here we get ourselfes the Profile datas
const profile_list = window.usersAll.map(obj => new Profile(
    obj["user_id"],
    obj["user_gender"],
    obj["user_age"],
    obj["user_kindOfGamer"],
    obj["user_game"],
    obj["lol_server"],
    obj["lol_main1"],
    obj["lol_main2"],
    obj["lol_main3"],
    obj["lol_rank"],
    obj["lol_role"],
    obj["usersServerValorant"],
    obj["usersMainValorant1"],
    obj["usersMainValorant2"],
    obj["usersMainValorant3"],
    obj["usersRankValorant"],
    obj["usersRoleValorant"],
    obj["lf_gender"],
    obj["lf_kindofgamer"],
    obj["lf_game"],
    obj["lf_lolmain1"],
    obj["lf_lolmain2"],
    obj["lf_lolmain3"],
    obj["lf_lolrank"],
    obj["lf_lolrole"],
    obj["usersMainValorant1Lf"],
    obj["usersMainValorant2Lf"],
    obj["usersMainValorant3Lf"],
    obj["usersRankValorantLf"],
    obj["usersRoleValorantLf"]
));

const user_profile = new Profile(
    window.user["user_id"],
    window.user["user_gender"],
    window.user["user_age"],
    window.user["user_kindOfGamer"],
    window.user["user_game"],
    window.user["lol_server"],
    window.user["lol_main1"],
    window.user["lol_main2"],
    window.user["lol_main3"],
    window.user["lol_rank"],
    window.user["lol_role"],
    window.user["usersServerValorant"],
    window.user["usersMainValorant1"],
    window.user["usersMainValorant2"],
    window.user["usersMainValorant3"],
    window.user["usersRankValorant"],
    window.user["usersRoleValorant"],
    window.user["lf_gender"],
    window.user["lf_kindofgamer"],
    window.user["lf_game"],
    window.user["lf_lolmain1"],
    window.user["lf_lolmain2"],
    window.user["lf_lolmain3"],
    window.user["lf_lolrank"],
    window.user["lf_lolrole"],
    window.user["usersMainValorant1Lf"],
    window.user["usersMainValorant2Lf"],
    window.user["usersMainValorant3Lf"],
    window.user["usersRankValorantLf"],
    window.user["usersRoleValorantLf"]
);

//Here we load the txt files
const baseUrl = window.location.href.replace(/\/[^/]+$/, '/');
let champion_list = [];
let champion_valo = [];

async function loadChampionsLol() {
    try {
        const response = await fetch(baseUrl + 'public/js/Champions.txt');
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        const text = await response.text();
        const lines = text.split('\n'); // Split text into lines

        lines.map(line => {
            const [name, lane, championtype, damagetype, speciality] = line.split(";")
            champion_list.push(
                new Champion(name, lane, championtype, damagetype, speciality.replace(/\r/g, ''))
            );
        });
    } catch (error) {
        console.error('There was a problem loading the file:', error);
        return null;
    }
}
async function loadChampionsValo() {
    try {
        const response = await fetch(baseUrl + 'public/js/Valorant_Champions.txt');
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        const text = await response.text();
        const lines = text.split('\n'); // Split text into lines

        lines.map(line => {
            const [name, champion_class] = line.split(";");
            champion_valo.push(
                new Champion_Valo(name, champion_class)
            );
        });
    } catch (error) {
        console.error('There was a problem loading the file:', error);
        return null;
    }
}

loadChampionsLol()
loadChampionsValo()


//Here are all the matching functions for the algorythm

//More simple champion matching functions since the other one went crazy
function champion_match_new(
    userMain1,
    userMain2,
    userMain3,
    userLookingMain1,
    userLookingMain2,
    userLookingMain3,
    matchMain1,
    matchMain2,
    matchMain3,
    matchLookingMain1,
    matchLookingMain2,
    matchLookingMain3,
) {
    //create an array for each user Mains and looking for champions
    var score=0
    const userMains = [userMain1, userMain2, userMain3];
    const userLookingMains = [userLookingMain1, userLookingMain2, userLookingMain3];
    const matchMains = [matchMain1, matchMain2, matchMain3];
    const matchLookingMains = [matchLookingMain1, matchLookingMain2, matchLookingMain3];

    // Compare the arrays 
    function findMatches(mainArray, lookingArray) {
        mainArray.forEach(champ => {
            if (lookingArray.includes(champ)) {
                score += 40;  // raise the score if a match is found
            }else {
                score -= 10;
            }
        });
    }
    //match the main user looking for to the match user mains
    // findMatches(userMains, matchLookingMains);
    findMatches(matchMains, userLookingMains);

    // Return the score
    return score;
}

//Very complex Champion Matching League
function champion_match(
    userMain1,
    userMain2,
    userMain3,
    userLookingMain1,
    userLookingMain2,
    userLookingMain3,
    matchMain1,
    matchMain2,
    matchMain3,
    matchLookingMain1,
    matchLookingMain2,
    matchLookingMain3,
) {
    const championsInUse = [0, 1, 2, 3, 4, 5];
    const lookingForChamps = [0, 1, 2, 3, 4, 5];


    for (let i = 0; i < champion_list.length; i++) {
        const Champion = champion_list[i];

        if (Champion.name === userMain1) {
            championsInUse[0] = Champion;
        } if (Champion.name === userMain2) {
            championsInUse[1] = Champion;
        } if (Champion.name === userMain3) {
            championsInUse[2] = Champion;
        } if (Champion.name === matchMain1) {
            championsInUse[3] = Champion;
        } if (Champion.name === matchMain2) {
            championsInUse[4] = Champion;
        } if (Champion.name === matchMain3) {
            championsInUse[5] = Champion;
        } if (Champion.name === userLookingMain1) {
            lookingForChamps[0] = Champion
        } if (Champion.name === userLookingMain2) {
            lookingForChamps[1] = Champion
        } if (Champion.name === userLookingMain3) {
            lookingForChamps[2] = Champion
        } if (Champion.name === matchLookingMain1) {
            lookingForChamps[3] = Champion
        } if (Champion.name === matchLookingMain2) {
            lookingForChamps[4] = Champion
        } if (Champion.name === matchLookingMain3) {
            lookingForChamps[5] = Champion
        }

    }
    const user_champions = championsInUse.slice(0, 3); // Select the first three champions
    const comparison_champions = championsInUse.slice(3); // Select the second three champions
    const user_lookingFor = lookingForChamps.slice(0, 3);// Select the first three champions
    const match_lookingFor = lookingForChamps.slice(3);// Select the second three champions

    let score = 0;

    for (let i = 0; i < user_lookingFor.length; i++) {
        const champion_user = user_lookingFor[i]
        for (let j = 0; j < comparison_champions.length; j++) {
            const champion_match = comparison_champions[j]

            if (champion_user.name === champion_match.name) {
                score += 20
            }
        }
    }

    for (let i = 0; i < match_lookingFor.length; i++) {
        const champion_user = match_lookingFor[i]
        for (let j = 0; j < user_champions.length; j++) {
            const champion_match = user_champions[j]
            if (champion_user.name === champion_match.name) {
                score += 20
            }
        }
    }



    for (let i = 0; i < comparison_champions.length; i++) {
        const champion = comparison_champions[i];
        for (let j = 0; j < user_champions.length; j++) {
            const user_champion = user_champions[j];

            if (user_champion.name === champion.name) {
                continue
            }
            // Compare lane
            score += compare_lane(champion.lane, user_champion.lane)

            // Compare class
            score += compare_class(champion.championtype, user_champion.championtype)

            // Compare damage type
            score += compare_damagetype(champion.damagetype, user_champion.damagetype)

            // Compare specialty
            score += compare_speciallty(champion.speciality, user_champion.speciality)



        }
    }

    return score;
}

function compare_lane(champion_lane, user_lane) {

    const lane_Scores = {
        'top-mid': 0.7,
        'top-jungle': 0.8,
        'top-adc': 0.2,
        'top-support': 0.2,
        'jungle-mid': 0.7,
        'jungle-adc': 0.7,
        'jungle-supp': 0.7,
        'jungle-top': 0.8,
        'mid-adc': 0.7,
        'mid-support': 0.8,
        'mid-jungle': 1,
        'mid-top': 0.7,
        'adc-top': 0.2,
        'adc-jungle': 0.7,
        'adc-mid': 0.7,
        'adc-support': 1,
        'support-top': 0.2,
        'support-jungle': 0.7,
        'support-mid': 0.8,
        'support-adc': 1
    };

    // Create lane combination string
    const laneCombination = `${champion_lane}-${user_lane}`;

    // Check if lane combination exists in laneScores object
    if (laneCombination in lane_Scores) {
        return lane_Scores[laneCombination];
    } else {
        const split_champion = champion_lane.split('/')
        const split_user = user_lane.split('/')
        var divider = 0
        var score = 0

        split_champion.forEach(element_champ => {
            split_user.forEach(element_user => {

                const laneCombination = `${element_champ}-${element_user}`;

                if (laneCombination in lane_Scores) {
                    divider++
                    score += lane_Scores[laneCombination]

                }
            })
        }

        )
        if (score != 0) { score = score / divider }
        return score
    }

}

function compare_class(champion_class, user_class) {

    class_synergy = {
        'bruiser-bruiser': 0.8,
        'bruiser-control mage': 0.6,
        'bruiser-assassin': 0.4,
        'bruiser-tank': 0.9,
        'bruiser-burst mage': 0.5,
        'bruiser-marksman': 0.3,
        'bruiser-utility mage': 0.7,
        'bruiser-dps mage': 0.5,
        'bruiser-tank/mage': 0.9,
        'bruiser-duelist': 0.9,
        'bruiser-support': 0.6,
        'bruiser-enchanter': 0.7,
        'bruiser-tank/bruiser': 0.9,
        'bruiser-fighter': 0.9,
        'control mage-bruiser': 0.6,
        'control mage-control mage': 0.8,
        'control mage-assassin': 0.4,
        'control mage-tank': 0.7,
        'control mage-burst mage': 0.8,
        'control mage-marksman': 0.4,
        'control mage-utility mage': 0.9,
        'control mage-dps mage': 0.8,
        'control mage-tank/mage': 0.8,
        'control mage-duelist': 0.6,
        'control mage-support': 0.9,
        'control mage-enchanter': 0.9,
        'control mage-tank/bruiser': 0.7,
        'control mage-fighter': 0.6,
        'assassin-bruiser': 0.4,
        'assassin-control mage': 0.4,
        'assassin-assassin': 0.8,
        'assassin-tank': 0.5,
        'assassin-burst mage': 0.7,
        'assassin-marksman': 0.2,
        'assassin-utility mage': 0.5,
        'assassin-dps mage': 0.7,
        'assassin-tank/mage': 0.5,
        'assassin-duelist': 0.9,
        'assassin-support': 0.4,
        'assassin-enchanter': 0.4,
        'assassin-tank/bruiser': 0.5,
        'assassin-fighter': 0.9,
        'tank-bruiser': 0.9,
        'tank-control mage': 0.7,
        'tank-assassin': 0.5,
        'tank-tank': 0.9,
        'tank-burst mage': 0.6,
        'tank-marksman': 0.4,
        'tank-utility mage': 0.8,
        'tank-dps mage': 0.7,
        'tank-tank/mage': 0.9,
        'tank-duelist': 0.9,
        'tank-support': 0.8,
        'tank-enchanter': 0.9,
        'tank-tank/bruiser': 0.9,
        'tank-fighter': 0.9,
        'burst mage-bruiser': 0.5,
        'burst mage-control mage': 0.8,
        'burst mage-assassin': 0.7,
        'burst mage-tank': 0.6,
        'burst mage-burst mage': 0.9,
        'burst mage-marksman': 0.3,
        'burst mage-utility mage': 0.8,
        'burst mage-dps mage': 0.9,
        'burst mage-tank/mage': 0.8,
        'burst mage-duelist': 0.7,
        'burst mage-support': 0.7,
        'burst mage-enchanter': 0.9,
        'burst mage-tank/bruiser': 0.6,
        'burst mage-fighter': 0.7,
        'marksman-bruiser': 0.3,
        'marksman-control mage': 0.4,
        'marksman-assassin': 0.2,
        'marksman-tank': 0.4,
        'marksman-burst mage': 0.3,
        'marksman-marksman': 0.9,
        'marksman-utility mage': 0.4,
        'marksman-dps mage': 0.6,
        'marksman-tank/mage': 0.4,
        'marksman-duelist': 0.2,
        'marksman-support': 0.9,
        'marksman-enchanter': 0.9,
        'marksman-tank/bruiser': 0.4,
        'marksman-fighter': 0.7,
        'utility mage-bruiser': 0.7,
        'utility mage-control mage': 0.9,
        'utility mage-assassin': 0.5,
        'utility mage-tank': 0.8,
        'utility mage-burst mage': 0.8,
        'utility mage-marksman': 0.4,
        'utility mage-utility mage': 0.9,
        'utility mage-dps mage': 0.8,
        'utility mage-tank/mage': 0.8,
        'utility mage-duelist': 0.7,
        'utility mage-support': 0.9,
        'utility mage-enchanter': 0.9,
        'utility mage-tank/bruiser': 0.8,
        'utility mage-fighter': 0.7,
        'dps mage-bruiser': 0.5,
        'dps mage-control mage': 0.8,
        'dps mage-assassin': 0.7,
        'dps mage-tank': 0.7,
        'dps mage-burst mage': 0.9,
        'dps mage-marksman': 0.6,
        'dps mage-utility mage': 0.8,
        'dps mage-dps mage': 0.9,
        'dps mage-tank/mage': 0.8,
        'dps mage-duelist': 0.7,
        'dps mage-support': 0.6,
        'dps mage-enchanter': 0.8,
        'dps mage-tank/bruiser': 0.7,
        'dps mage-fighter': 0.8,
        'tank/mage-bruiser': 0.9,
        'tank/mage-control mage': 0.8,
        'tank/mage-assassin': 0.5,
        'tank/mage-tank': 0.9,
        'tank/mage-burst mage': 0.8,
        'tank/mage-marksman': 0.4,
        'tank/mage-utility mage': 0.8,
        'tank/mage-dps mage': 0.8,
        'tank/mage-tank/mage': 0.9,
        'tank/mage-duelist': 0.8,
        'tank/mage-support': 0.9,
        'tank/mage-enchanter': 0.9,
        'tank/mage-tank/bruiser': 0.9,
        'tank/mage-fighter': 0.9,
        'duelist-bruiser': 0.9,
        'duelist-control mage': 0.6,
        'duelist-assassin': 0.9,
        'duelist-tank': 0.9,
        'duelist-burst mage': 0.7,
        'duelist-marksman': 0.2,
        'duelist-utility mage': 0.7,
        'duelist-dps mage': 0.7,
        'duelist-tank/mage': 0.8,
        'duelist-duelist': 0.9,
        'duelist-support': 0.5,
        'duelist-enchanter': 0.5,
        'duelist-tank/bruiser': 0.9,
        'duelist-fighter': 0.9,
        'support-bruiser': 0.6,
        'support-control mage': 0.9,
        'support-assassin': 0.4,
        'support-tank': 0.8,
        'support-burst mage': 0.7,
        'support-marksman': 0.9,
        'support-utility mage': 0.9,
        'support-dps mage': 0.6,
        'support-tank/mage': 0.9,
        'support-duelist': 0.5,
        'support-support': 1.0,
        'support-enchanter': 0.9,
        'support-tank/bruiser': 0.8,
        'support-fighter': 0.7,
        'enchanter-bruiser': 0.7,
        'enchanter-control mage': 0.9,
        'enchanter-assassin': 0.4,
        'enchanter-tank': 0.9,
        'enchanter-burst mage': 0.9,
        'enchanter-marksman': 0.9,
        'enchanter-utility mage': 0.9,
        'enchanter-dps mage': 0.8,
        'enchanter-tank/mage': 0.9,
        'enchanter-duelist': 0.5,
        'enchanter-support': 0.9,
        'enchanter-enchanter': 0.9,
        'enchanter-tank/bruiser': 0.8,
        'enchanter-fighter': 0.7,
        'tank/bruiser-bruiser': 0.9,
        'tank/bruiser-control mage': 0.7,
        'tank/bruiser-assassin': 0.5,
        'tank/bruiser-tank': 0.9,
        'tank/bruiser-burst mage': 0.6,
        'tank/bruiser-marksman': 0.4,
        'tank/bruiser-utility mage': 0.8,
        'tank/bruiser-dps mage': 0.7,
        'tank/bruiser-tank/mage': 0.9,
        'tank/bruiser-duelist': 0.9,
        'tank/bruiser-support': 0.8,
        'tank/bruiser-enchanter': 0.8,
        'tank/bruiser-tank/bruiser': 0.9,
        'tank/bruiser-fighter': 0.9,
        'fighter-bruiser': 0.9,
        'fighter-control mage': 0.6,
        'fighter-assassin': 0.9,
        'fighter-tank': 0.9,
        'fighter-burst mage': 0.7,
        'fighter-marksman': 0.7,
        'fighter-utility mage': 0.7,
        'fighter-dps mage': 0.8,
        'fighter-tank/mage': 0.9,
        'fighter-duelist': 0.9,
        'fighter-support': 0.7,
        'fighter-enchanter': 0.7,
        'fighter-tank/bruiser': 0.9,
        'fighter-fighter': 0.9,
    }


    const classCombination = `${champion_class}-${user_class}`;

    if (classCombination in class_synergy) {
        return class_synergy[classCombination];
    }

    return 0;

}

function compare_damagetype(champion_dmgtype, user_dmgtype) {
    const dmgtype_Scores = {
        'AD-AD': 0.5,
        'AD-AP': 1,
        'AP-AD': 1,
        'AP-AP': 0.5,
        'AD + AP-AD': 1,
        'AD + AP-AP': 1
    };

    const dmgCombination = `${champion_dmgtype}-${user_dmgtype}`;

    if (dmgCombination in dmgtype_Scores) {
        return dmgtype_Scores[dmgCombination];
    }

    return 0;

}

function compare_speciallty(champion_ability, user_ability) {
    var score = 0
    const abilityScores = {
        'mobile': 0.9,
        'CC': 0.7,
        'utility': 0.6,
        'burst': 0.8,
        'sustain': 0.5,
        'crowd control': 0.7,
        'engage': 0.6,
        'zone control': 0.5,
        'roaming': 0.6,
        'high dps': 0.8,
        'AoE damage': 0.6,
        'long-range': 0.5,
        'poke': 0.7,
        'melee': 0.6,
        'stealth': 0.7,
        'healing': 0.7,
        'protection': 0.7,
        'global presence': 0.6,
        'scaling': 0.5,
        'mobility': 0.8,
        'utility+burst': 0.9,
        'utility+sustain': 0.9,
        'utility+CC': 0.9,
        'engage+burst': 0.9,
        'engage+sustain': 0.9,
        'engage+CC': 0.9,
        'crowd control+burst': 0.9,
        'crowd control+sustain': 0.9,
        'crowd control+utility': 0.9,
        'mobile+CC': 0.9,
        'mobile+utility': 0.7,
        'mobile+burst': 0.9,
        'mobile+sustain': 0.9,
        'mobile+engage': 0.7,
        'mobile+crowd control': 0.7,
        'mobile+utility+burst': 0.9,
        'mobile+utility+sustain': 0.9,
        'mobile+utility+CC': 0.9,
        'engage+mobile': 0.7,
        'engage+mobile+CC': 0.9,
        'engage+mobile+utility': 0.7,
        'crowd control+mobile': 0.7,
        'crowd control+mobile+CC': 0.9,
        'crowd control+mobile+utility': 0.7
    }

    if (champion_ability in abilityScores) {
        score += abilityScores[champion_ability];
    }
    if (user_ability in abilityScores) {
        score += abilityScores[user_ability];
    }
    if (score != 0) {
        return score / 2
    } else return 0

}

// Other Matching Functions for Lol
function matchRankLol(match_rank, user_rank) {
    const rankScore = {
        'Unranked': 1,
        'Iron': 2,
        'Bronze': 3,
        'Silver': 4,
        'Gold': 5,
        'Platinum': 6,
        'Emerald':7,
        'Diamond': 8,
        'Master': 9,
        'Grandmaster': 10,
        'Challenger': 11,
        'Any': 11
    }
    if (match_rank in rankScore && user_rank in rankScore) {
        const score1 = rankScore[match_rank];
        const score2 = rankScore[user_rank];
        var score = 0
        if (score1 > score2) {
            score = score1 - score2
        } else if (score1 < score2) {
            score = score2 - score1
        }
        if (score <= 1) {
            return 20
        } else { return 0 }
    }


}

function matchAge(match_age, user_age) {


    const minAge = user_age /2 + 7
    const maxAge = (user_age-7) * 2 

    var ageDiff = Math.abs(match_age - user_age);
    var maxAgeDiff = 100;
    var score = 10 - (ageDiff / maxAgeDiff) * 9;

    if (match_age < minAge){
        score += 60;
    }

    if(match_age < maxAge){
        score += 60;
    }
    

    return score;
}

function matchGame(profile_game, profile_looking, user_game, user_lookingFor) {

    if (
        (profile_game === "both" || user_game === "both") && profile_looking === user_lookingFor
    ) {
        return user_lookingFor;
    }

    if (profile_game === user_game && profile_looking === user_lookingFor) {
        return user_game;
    }

    return "No match";

    // Find the intersection of the two sets to get the common games
    // const commonGames = new Set([...userGamesSet].filter(game => profileGamesSet.has(game)));

    // if (user_lookingFor.includes("both") && user_lookingFor.includes("both")) {
    //     return "both"
    // }

    // if ((user_lookingFor.includes("Valorant") && profile_game.includes("Valorant")) || (user_lookingFor.includes("League of Legends") && profile_game.includes("League of Legends"))) {
    //     if (user_game.includes("Valorant") && profile_looking.includes("Valorant")) {
    //         return "both"
    //     }
    // }

    // if (user_lookingFor.includes("Valorant") && profile_game.includes("Valorant")) {
    //     if (user_game.includes("Valorant") && profile_looking.includes("Valorant")) {
    //         return "Valorant"
    //     }
    // }

    // if (user_lookingFor.includes("Valorant") && profile_game.includes("both")) {
    //     if (user_game.includes("both") && profile_looking.includes("Valorant")) {
    //         return "Valorant"
    //     }
    // }


    // if (user_lookingFor.includes("League of Legends") && profile_game.includes("League of Legends")) {
    //     if (user_game.includes("League of Legends") && profile_looking.includes("League of Legends")) {
    //         return "League of Legends"
    //     }
    // }
    // if (user_lookingFor.includes("League of Legends") && profile_game.includes("both")) {
    //     if (user_game.includes("both") && profile_looking.includes("League of Legends")) {
    //         return "League of Legends"
    //     }
    // }


    // return "No Match"
}

// Matching functions Valorant
function matchRankValo(match_rank, user_rank) {
    const rankScore = {
        'Iron': 1,
        'Bronze': 2,
        'Silver': 3,
        'Gold': 4,
        'Platinum': 5,
        'Diamond': 6,
        'Ascendant': 7,
        'Immortal': 8,
        'Radiant': 9,
    }
    if (match_rank in rankScore && user_rank in rankScore) {
        const score1 = rankScore[match_rank];
        const score2 = rankScore[user_rank];
        var score = 0
        if (score1 > score2) {
            score = score1 - score2
        } else if (score1 < score2) {
            score = score2 - score1
        }
        if (score <= 1) {
            return 20
        } else { return 0 }
    }


}

//Valorant Champion Matching
function championValorant(
    userMain1,
    userMain2,
    userMain3,
    userLookingMain1,
    userLookingMain2,
    userLookingMain3,
    matchMain1,
    matchMain2,
    matchMain3,
    matchLookingMain1,
    matchLookingMain2,
    matchLookingMain3,
) {

    const championsInUse = [0, 1, 2, 3, 4, 5];
    const lookingForChamps = [0, 1, 2, 3, 4, 5];

    for (let i = 0; i < champion_valo.length; i++) {
        const Champion = champion_valo[i];

        if (Champion.name === userMain1) {
            championsInUse[0] = Champion;
        } if (Champion.name === userMain2) {
            championsInUse[1] = Champion;
        } if (Champion.name === userMain3) {
            championsInUse[2] = Champion;
        } if (Champion.name === matchMain1) {
            championsInUse[3] = Champion;
        } if (Champion.name === matchMain2) {
            championsInUse[4] = Champion;
        } if (Champion.name === matchMain3) {
            championsInUse[5] = Champion;
        } if (Champion.name === userLookingMain1) {
            lookingForChamps[0] = Champion
        } if (Champion.name === userLookingMain2) {
            lookingForChamps[1] = Champion
        } if (Champion.name === userLookingMain3) {
            lookingForChamps[2] = Champion
        } if (Champion.name === matchLookingMain1) {
            lookingForChamps[3] = Champion
        } if (Champion.name === matchLookingMain2) {
            lookingForChamps[4] = Champion
        } if (Champion.name === matchLookingMain3) {
            lookingForChamps[5] = Champion
        }
    }

    const user_champions = championsInUse.slice(0, 3); // Select the first three champions
    const comparison_champions = championsInUse.slice(3); // Select the second three champions
    const user_lookingFor = lookingForChamps.slice(0, 3);// Select the first three champions
    const match_lookingFor = lookingForChamps.slice(3);// Select the second three champions

    let score = 0;


    for (let i = 0; i < comparison_champions.length; i++) {
        const champion = comparison_champions[i];
        for (let j = 0; j < user_champions.length; j++) {
            const user_champion = user_champions[j]
            score += calculateAgentSynergy(champion, user_champion)
        }
    }

    for (let i = 0; i < user_lookingFor.length; i++) {
        const champion_user = user_lookingFor[i]
        for (let j = 0; j < comparison_champions.length; j++) {
            const champion_match = comparison_champions[j]

            if (champion_user.name === champion_match.name) {
                score += 20
            }
        }
    }

    for (let i = 0; i < match_lookingFor.length; i++) {
        const champion_user = match_lookingFor[i]
        for (let j = 0; j < user_champions.length; j++) {
            const champion_match = user_champions[j]
            if (champion_user.name === champion_match.name) {
                score += 20
            }
        }
    }


    // Function to calculate synergy score between two agents
    function calculateAgentSynergy(champion, user_champion) {
        // Role compatibility score
        const roleScore = calculateRoleCompatibility(champion.champion_class, user_champion.champion_class);

        // Calculate overall synergy score
        const synergyScore = roleScore
        return synergyScore;
    }

    // Function to calculate role compatibility score
    function calculateRoleCompatibility(role1, role2) {
        // Assign weights to role compatibility scores based on importance
        const roleWeights = {
            Duelist: 1,
            Controller: 0.8,
            Sentinel: 0.6,
            Initiator: 0.7,
        };

        // Check if roles are the same
        if (role1 === role2) {
            return roleWeights[role1];
        }

        // Return lower weight for different roles
        return Math.min((roleWeights[role1], roleWeights[role2]) * 10);
    }


    return score
}


//All Matching
function match_profiles(profile_list, user_profile) {
    for (let i = 0; i < profile_list.length; i++) {
        const profile = profile_list[i];
        var finalScore = 0
        // Match games
        // const game = matchGame(profile.game, profile.lookingGame, user_profile.game, user_profile.lookingGame)
        const game = "League of Legends"
        switch (game) {
            case "League of Legends":
                finalScore += champion_match_new(
                    user_profile.mainLol1,
                    user_profile.mainLol2,
                    user_profile.mainLol3,
                    user_profile.lookingMainLol1,
                    user_profile.lookingMainLol2,
                    user_profile.lookingMainLol3,
                    profile.mainLol1,
                    profile.mainLol2,
                    profile.mainLol3,
                    profile.lookingMainLol1,
                    profile.lookingMainLol2,
                    profile.lookingMainLol3,

                );
                //Matching for rank
                finalScore += matchRankLol(profile.rankLol, user_profile.rankLol)
                break;
            case "Valorant":
                finalScore += championValorant(
                    user_profile.mainValo1,
                    user_profile.mainValo2,
                    user_profile.mainValo3,
                    user_profile.lookingMainValo1,
                    user_profile.lookingMainValo2,
                    user_profile.lookingMainValo3,
                    profile.mainValo1,
                    profile.mainValo2,
                    profile.mainValo3,
                    profile.lookingMainValo1,
                    profile.lookingMainValo2,
                    profile.lookingMainValo3,

                );
                //Matching for rank
                finalScore += matchRankValo(profile.rankValo, user_profile.rankValo)
                break
            case "both":

                finalScore += champion_match(
                    user_profile.mainLol1,
                    user_profile.mainLol2,
                    user_profile.mainLol3,
                    user_profile.lookingMainLol1,
                    user_profile.lookingMainLol2,
                    user_profile.lookingMainLol3,
                    profile.mainLol1,
                    profile.mainLol2,
                    profile.mainLol3,
                    profile.lookingMainLol1,
                    profile.lookingMainLol2,
                    profile.lookingMainLol3,

                );

                finalScore += championValorant(
                    user_profile.mainValo1,
                    user_profile.mainValo2,
                    user_profile.mainValo3,
                    user_profile.lookingMainValo1,
                    user_profile.lookingMainValo2,
                    user_profile.lookingMainValo3,
                    profile.mainValo1,
                    profile.mainValo2,
                    profile.mainValo3,
                    profile.lookingMainValo1,
                    profile.lookingMainValo2,
                    profile.lookingMainValo3,

                );
                //Matching for rank
                finalScore += matchRankLol(profile.rankLol, user_profile.rankLol)
                finalScore += matchRankValo(profile.rankValo, user_profile.rankValo)
                finalScore = finalScore / 2
                break
            case "No Match":
                finalScore = finalScore - 40
                break
        }

        //Match Server
        if (profile.server === user_profile.server) {
            finalScore += 10
        }
        console.log("Server: ",finalScore)

        //Match kind of gamer
        if (user_profile.lookingGamerkind === "competition and chill") {
            finalScore += 40
        } else if (user_profile.lookingGamerkind === profile.gamerkind) {
            finalScore += 40
        } else if (profile.gamerkind === "competition and chill") {
            finalScore += 40
        }

        if (profile.lookingGamerkind === "competition and chill") {
            finalScore += 40
        } else if (user_profile.lookingGamerkind === profile.gamerkind) {
            finalScore += 40
        } else if (user_profile.gamerkind === "competition and chill") {
            finalScore += 40
        }

        console.log("kind of gamer: ",finalScore)

        //Same role gives negative points and if role fits the looking for role it gives points
        if (profile.role === user_profile.role) {
            finalScore += -50
        }
        
        if (profile.role === user_profile.lookingRole) {
            finalScore += 10
        }

        if (profile.lookingRole === user_profile.role) {
            finalScore += 10
        }
        console.log("role: ",finalScore)

        //Sort by the gender they selected
        if (profile.gender === user_profile.lookingGender) {
            finalScore += 60
        } else if (user_profile.lookingGender === "All") {
            finalScore += 40
        } else {
            finalScore -= 40
        }
        if (profile.lookingGender === user_profile.gender) {
            finalScore += 60
        } else if (profile.lookingGender === "All") {
            finalScore += 40
        } else {
            finalScore -= 40
        }
        console.log("gender: ",finalScore)

        //Matching for age
        finalScore += matchAge(profile.age, user_profile.age)
        console.log("age: ",finalScore)

        profile.score = finalScore

    }

    return profile_list.sort((a, b) => b.score - a.score);
}

//function to send data to php

function sendDataToPHP(dataToSend) {
    // Stringify the array of objects
    const jsonData = JSON.stringify(dataToSend);

    // Create a new fetch request
    fetch('index.php?action=algoData', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: "param=" + jsonData
    })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response; // Parse response as JSON
        })
        .then(data => {
        })
        .catch(error => {
            console.error('Error:', error);
        });
}


//Now everything should be prepared to run the algorythm
if (champion_list && champion_valo) {

    const matched_profiles = match_profiles(profile_list, user_profile).filter((obj) => {
        return user_profile.userid !== obj.userid;
    });

    console.log(matched_profiles)
    sendDataToPHP(matched_profiles.map((obj) => {
        const dataToSend = ({
            user_id: user_profile.userid,
            user_matching: obj.userid,
            score: obj.score,
        })
        return dataToSend
    }))

}





