 function TextWrite() {
            document.getElementById("InfoText").innerText =
                "Info: Name - Yuval Palombo, Age - 25, Height - 180 cm, Student at Ruppin Academic Center, 3rd year in computer engineering";
        }


        function toggleContact() {
            let contactElement = document.getElementById("contact");
            if (!contactElement) {
                contactElement = document.createElement("h3");
                contactElement.id = "contact";
                contactElement.innerHTML = "Contact Info: yuvalplmb271@gmail.com <br> Phone number: 0544935019";
                document.body.appendChild(contactElement);
            } else {
                document.body.removeChild(contactElement);
            }
        }

        TextWrite();
       