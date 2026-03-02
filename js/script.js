console.log("script loaded");

let events = [
    {
        title: "Tech Hackathon 2026",
        description: "Showcase your coding skills and build innovative solutions.",
        date: "March 12, 2026",
        status: "approved"
    },
    {
        title: "Cultural Fest",
        description: "Music, dance, and creative performances across departments.",
        date: "April 5, 2026",
        status: "approved"
    },
    {
        title: "Startup Pitch Day",
        description: "Pitch your ideas to investors and industry experts.",
        date: "May 20, 2026",
        status: "pending"
    }
];



function renderEvents() {
    const myEvents = document.getElementById("myEvents");
    const adminEvents = document.getElementById("adminEvents");
    const trending = document.getElementById("eventsContainer");

    if (myEvents) myEvents.innerHTML = "";
    if (adminEvents) adminEvents.innerHTML = "";
    if (trending) trending.innerHTML = "";

    events.forEach((event, index) => {

        if (myEvents) {
            myEvents.innerHTML += `
                <div class="event-card">
                    <h3>${event.title}</h3>
                    <p>${event.date}</p>
                   <p>Status: <span class="status-${event.status}">${event.status}</span></p>
                </div>
            `;
        }

        if (adminEvents) {
            adminEvents.innerHTML += `
                <div class="event-card">
                    <h3>${event.title}</h3>
                    <button onclick="approveEvent(${index})">Approve</button>
                    <button onclick="rejectEvent(${index})">Reject</button>
                </div>
            `;
        }

        if (trending && event.status === "approved") {
            trending.innerHTML += `
                <div class="event-card">
                    <h3>${event.title}</h3>
                    <p>${event.date}</p>
                </div>
            `;
        }
    });
}
renderEvents();
function approveEvent(index) {
    events[index].status = "approved";
    renderEvents();
}

function rejectEvent(index) {
    events[index].status = "rejected";
    renderEvents();
}